<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantStock;
use App\Models\Sale;
use App\Models\SaleHasProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    private function tenantId()
    {
        return auth()->user()->tenant_id;
    }

    private function isOwner(): bool
    {
        return auth()->user()->role
            && strcasecmp(auth()->user()->role->name, 'Owner') === 0;
    }

    private function resolveBranch($requestedBranchId = null)
    {
        $branches = Branch::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get();

        if ($branches->isEmpty()) {
            return null;
        }

        if ($this->isOwner()) {
            if (!$requestedBranchId) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Owner harus memilih cabang terlebih dahulu.',
                ]);
            }

            return $branches->firstWhere(
                'id',
                (int) $requestedBranchId
            ) ?? throw ValidationException::withMessages([
                    'branch_id' => 'Cabang tidak valid.',
                ]);
        }

        $branch = $branches->firstWhere(
            'id',
            auth()->user()->branch_id
        );

        if (!$branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'Akun belum memiliki cabang yang valid.',
            ]);
        }

        return $branch;
    }


    public function index()
    {
        $branches = Branch::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $isOwner = $this->isOwner();

        $selectedBranchId = $isOwner
            ? session('pos_branch_id', $branches->first()?->id)
            : auth()->user()->branch_id;

        // Jika cabang tersimpan sudah tidak tersedia, kembali ke cabang pertama
        if ($isOwner && !$branches->contains('id', $selectedBranchId)) {
            $selectedBranchId = $branches->first()?->id;
        }

        return view('pos.index', compact(
            'branches',
            'isOwner',
            'selectedBranchId'
        ));
    }

    public function selectBranch(Request $request)
    {
        if (!$this->isOwner()) {
            abort(403);
        }

        $data = $request->validate([
            'branch_id' => ['required', 'integer'],
        ]);

        $branch = $this->resolveBranch($data['branch_id']);

        session(['pos_branch_id' => $branch->id]);

        return response()->json([
            'message' => 'Cabang berhasil dipilih.'
        ]);
    }

    public function products(Request $request)
    {
        $branch = $this->resolveBranch(
            $request->input('branch_id')
        );

        $products = Product::with([
            'category',
            'variants' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('name');
            },
            'variants.stocks' => function ($query) use ($branch) {
                if ($branch) {
                    $query->where('branch_id', $branch->id);
                } else {
                    $query->whereNull('branch_id');
                }
            },
        ])
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'active')
            ->whereHas('category', function ($query) {
                $query->where('tenant_id', $this->tenantId());
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($variantQuery) use ($search) {
                            $variantQuery->where('status', 'active')
                                ->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->category_id);
            })
            ->orderBy('name')
            ->get();

        $data = $products->map(function ($product) {
            $variants = $product->variants
                ->filter(function ($variant) {
                    return $variant->stocks->isNotEmpty();
                })
                ->map(function ($variant) {
                    $stock = $variant->stocks->sum('stock');

                    return [
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'price' => (float) $variant->price,
                        'image' => $variant->image,
                        'stock' => (float) $stock,
                        'available' => $stock > 0,
                    ];
                })
                ->values();

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $product->category->name,
                'variants' => $variants,
            ];
        })
            ->filter(function ($product) {
                return $product['variants']->isNotEmpty();
            })
            ->values();

        return response()->json([
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
            ] : null,
            'products' => $data,
        ]);
    }


    public function checkout(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'payment_method' => ['required', 'in:cash,qris'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => [
                'required',
                'integer',
                'distinct',
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $branch = $this->resolveBranch(
            $data['branch_id'] ?? null
        );

        $sale = DB::transaction(function () use ($data, $branch) {
            $items = [];
            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $variant = ProductVariant::whereKey($item['variant_id'])
                    ->where('status', 'active')
                    ->whereHas('product', function ($query) {
                        $query->where('tenant_id', $this->tenantId())
                            ->where('status', 'active');
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    throw ValidationException::withMessages([
                        'items' => 'Terdapat produk yang tidak valid.',
                    ]);
                }

                // Pastikan harga jual sudah ditetapkan.
                if ((float) $variant->price <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Harga jual ' . ($variant->product?->name ?? 'Produk') . ' - ' . $variant->name . ' belum ditetapkan.',
                    ]);
                }

                $quantity = (float) $item['quantity'];

                // Validasi awal stok. Stok diperiksa ulang saat pembayaran
                // dikonfirmasi untuk mencegah stok menjadi negatif.
                $stockQuery = ProductVariantStock::where(
                    'product_variant_id',
                    $variant->id
                );

                if ($branch) {
                    $stockQuery->where('branch_id', $branch->id);
                } else {
                    $stockQuery->whereNull('branch_id');
                }

                $availableStock = (float) $stockQuery->sum('stock');

                if ($availableStock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Stok ' . ($variant->product?->name ?? 'Produk') . ' - ' . $variant->name . ' tidak mencukupi.',
                    ]);
                }

                $unitPrice = (float) $variant->price;
                $subtotal = round($unitPrice * $quantity, 2);

                $totalAmount += $subtotal;

                $items[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            }

            $totalAmount = round($totalAmount, 2);

            $cashReceived = null;
            $changeAmount = 0;
            $paymentStatus = 'pending';

            if ($data['payment_method'] === 'cash') {
                $cashReceived = (float) ($data['cash_received'] ?? 0);

                if ($cashReceived < $totalAmount) {
                    throw ValidationException::withMessages([
                        'cash_received' =>
                            'Uang yang diterima kurang dari total pembayaran.',
                    ]);
                }

                $changeAmount = round(
                    $cashReceived - $totalAmount,
                    2
                );

                $paymentStatus = 'paid';
            }

            $sale = Sale::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $branch?->id,
                'sale_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'],
                'payment_status' => $paymentStatus,
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
            ]);

            foreach ($items as $item) {
                SaleHasProductVariant::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $item['variant']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            // Tunai langsung lunas sehingga stok dikurangi sekarang.
            // QRIS menunggu konfirmasi, sehingga stok belum dikurangi.
            if ($paymentStatus === 'paid') {
                $this->deductSaleStock($sale);
            }

            return $sale;
        });

        return response()->json([
            'message' => $sale->payment_status === 'paid'
                ? 'Transaksi berhasil disimpan.'
                : 'Transaksi QRIS dibuat dan menunggu konfirmasi pembayaran.',
            'sale_id' => $sale->id,
            'total_amount' => (float) $sale->total_amount,
            'payment_method' => $sale->payment_method,
            'payment_status' => $sale->payment_status,
            'cash_received' => $sale->cash_received,
            'change_amount' => (float) $sale->change_amount,
        ], 201);
    }



    public function confirmPayment(Sale $sale)
    {
        if ((int) $sale->tenant_id !== (int) $this->tenantId()) {
            abort(404);
        }

        if (
            !$this->isOwner() &&
            (int) $sale->branch_id !== (int) auth()->user()->branch_id
        ) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($sale) {
                $lockedSale = Sale::whereKey($sale->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $lockedSale->tenant_id !== (int) $this->tenantId()) {
                    abort(404);
                }

                if (
                    !$this->isOwner() &&
                    (int) $lockedSale->branch_id !== (int) auth()->user()->branch_id
                ) {
                    abort(403);
                }

                if ($lockedSale->payment_method !== 'qris') {
                    throw ValidationException::withMessages([
                        'payment' => 'Konfirmasi ini hanya untuk pembayaran QRIS.',
                    ]);
                }

                if ($lockedSale->payment_status !== 'pending') {
                    throw ValidationException::withMessages([
                        'payment' => 'Transaksi sudah diproses sebelumnya.',
                    ]);
                }

                $this->deductSaleStock($lockedSale);

                $lockedSale->update([
                    'payment_status' => 'paid',
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Konfirmasi pembayaran gagal. Silakan periksa stok dan coba kembali.',
            ], 422);
        }

        return response()->json([
            'message' => 'Pembayaran QRIS berhasil dikonfirmasi.',
            'sale_id' => $sale->id,
            'payment_status' => 'paid',
        ]);
    }


    private function deductSaleStock(Sale $sale): void
    {
        $details = SaleHasProductVariant::where('sale_id', $sale->id)
            ->orderBy('product_variant_id')
            ->get();

        if ($details->isEmpty()) {
            throw ValidationException::withMessages([
                'stock' => 'Detail transaksi tidak ditemukan.',
            ]);
        }

        foreach ($details as $detail) {
            $variant = ProductVariant::whereKey($detail->product_variant_id)
                ->whereHas('product', function ($query) use ($sale) {
                    $query->where('tenant_id', $sale->tenant_id);
                })
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                throw ValidationException::withMessages([
                    'stock' => 'Produk transaksi tidak valid.',
                ]);
            }

            $stockQuery = ProductVariantStock::where(
                'product_variant_id',
                $variant->id
            );

            if ($sale->branch_id !== null) {
                $stockQuery->where('branch_id', $sale->branch_id);
            } else {
                $stockQuery->whereNull('branch_id');
            }

            $stocks = $stockQuery
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $availableStock = (float) $stocks->sum('stock');
            $requiredQuantity = (float) $detail->quantity;

            if ($requiredQuantity <= 0 || $availableStock < $requiredQuantity) {
                throw ValidationException::withMessages([
                    'stock' => 'Stok ' . ($variant->product?->name ?? 'Produk') . ' - ' . $variant->name . ' tidak mencukupi.',
                ]);
            }

            foreach ($stocks as $stock) {
                if (
                    (float) $stock->stock > 0 &&
                    $stock->average_unit_cost === null
                ) {
                    throw ValidationException::withMessages([
                        'stock' => 'HPP persediaan ' . ($variant->product?->name ?? 'Produk') . ' - ' . $variant->name .
                            ' di cabang ' . (Branch::whereKey($sale->branch_id)->where('tenant_id', $sale->tenant_id)->value('name') ?? 'terkait') .
                            ' belum ditentukan.',
                    ]);
                }
            }

            $inventoryValue = $stocks->sum(
                fn($stock) =>
                    (float) $stock->stock *
                    (float) ($stock->average_unit_cost ?? 0)
            );

            $unitCost = round($inventoryValue / $availableStock, 2);

            $remaining = $requiredQuantity;

            foreach ($stocks as $stock) {
                if ($remaining <= 0) {
                    break;
                }

                $deduction = min(
                    (float) $stock->stock,
                    $remaining
                );

                if ($deduction > 0) {
                    $stock->decrement('stock', $deduction);
                    $remaining -= $deduction;
                }
            }

            SaleHasProductVariant::where('sale_id', $sale->id)
                ->where('product_variant_id', $detail->product_variant_id)
                ->update([
                    'unit_cost' => $unitCost,
                ]);
        }
    }

    public function cancelPayment(Sale $sale)
    {
        if ((int) $sale->tenant_id !== (int) $this->tenantId()) {
            abort(404);
        }

        if (
            !$this->isOwner() &&
            (int) $sale->branch_id !== (int) auth()->user()->branch_id
        ) {
            abort(403);
        }

        DB::transaction(function () use ($sale) {
            $lockedSale = Sale::whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedSale->tenant_id !== (int) $this->tenantId()) {
                abort(404);
            }

            if (
                !$this->isOwner() &&
                (int) $lockedSale->branch_id !== (int) auth()->user()->branch_id
            ) {
                abort(403);
            }

            if (
                $lockedSale->payment_method !== 'qris' ||
                $lockedSale->payment_status !== 'pending'
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'Hanya pembayaran QRIS pending yang dapat dibatalkan.',
                ]);
            }

            $lockedSale->update([
                'payment_status' => 'cancelled',
            ]);
        });

        return response()->json([
            'message' => 'Transaksi QRIS berhasil dibatalkan.',
            'sale_id' => $sale->id,
            'payment_status' => 'cancelled',
        ]);
    }
}
