<?php

namespace App\Http\Controllers;

use App\Models\CustomOrder;
use App\Models\CustomOrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomOrderPaymentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $orders = CustomOrder::with('branch')
            ->withSum('payments', 'amount')
            ->where('tenant_id', $user->tenant_id)
            ->when($user->role?->name !== 'Owner', function ($query) use ($user) {
                $query->where('branch_id', $user->branch_id);
            })
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->latest()
            ->get();

        return view('custom_order_payments.index', compact('orders'));
    }

    public function show(CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);

        if ($customOrder->status === 'draft') {
            return redirect()
                ->route('custom_orders.show', $customOrder)
                ->with('error', 'Pesanan harus dikonfirmasi terlebih dahulu.');
        }

        $customOrder->load([
            'branch',
            'payments' => fn($query) => $query->orderBy('id'),
        ]);

        $totalPrice = (float) $customOrder->selling_price;
        $paid = (float) $customOrder->payments->sum('amount');
        $remaining = max(0, round($totalPrice - $paid, 2));

        return view('custom_order_payments.show', compact(
            'customOrder',
            'totalPrice',
            'paid',
            'remaining'
        ));
    }

    public function store(Request $request, CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);

        if (!in_array(auth()->user()->role?->name, ['Owner', 'Kasir'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'payment_type' => ['required', 'in:dp,pelunasan'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:Tunai,QRIS,Transfer'],
            'payment_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ]);

        DB::transaction(function () use ($customOrder, $data) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($order->status, ['draft', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'payment_type' => 'Pesanan belum dikonfirmasi atau sudah dibatalkan.',
                ]);
            }

            if ($order->selling_price === null || $order->selling_price <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Harga jual pesanan belum ditetapkan.',
                ]);
            }

            if ($data['payment_date'] < $order->order_date) {
                throw ValidationException::withMessages([
                    'payment_date' => 'Tanggal pembayaran tidak boleh sebelum tanggal pesanan.',
                ]);
            }

            $paid = (float) $order->payments()->sum('amount');
            $remaining = round((float) $order->selling_price - $paid, 2);
            $amount = (float) $data['amount'];

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Pesanan ini sudah lunas.',
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran melebihi sisa tagihan.',
                ]);
            }

            if ($data['payment_type'] === 'dp') {
                if ($order->payments()->exists()) {
                    throw ValidationException::withMessages([
                        'payment_type' => 'DP sudah dicatat. Gunakan pembayaran pelunasan.',
                    ]);
                }

                if ($amount >= $remaining) {
                    throw ValidationException::withMessages([
                        'amount' => 'DP harus lebih kecil dari total tagihan.',
                    ]);
                }
            }

            if ($data['payment_type'] === 'pelunasan') {
                if (abs($amount - $remaining) > 0.001) {
                    throw ValidationException::withMessages([
                        'amount' => 'Pelunasan harus sama dengan sisa tagihan.',
                    ]);
                }
            }

            $order->payments()->create([
                'payment_type' => $data['payment_type'],
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
            ]);
        });

        return redirect()
            ->route('custom_order_payments.show', $customOrder)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function receipt(CustomOrder $customOrder, CustomOrderPayment $payment)
    {
        $this->authorizeOrder($customOrder);

        $payment = $customOrder->payments()
            ->whereKey($payment->id)
            ->firstOrFail();

        $customOrder->load(['tenant', 'branch']);

        $paidBefore = (float) $customOrder->payments()
            ->where('id', '<', $payment->id)
            ->sum('amount');

        $totalPrice = (float) $customOrder->selling_price;
        $amount = (float) $payment->amount;
        $paidAfter = $paidBefore + $amount;
        $remaining = max(0, $totalPrice - $paidAfter);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('custom_order_payments.receipt', compact(
            'customOrder',
            'payment',
            'totalPrice',
            'paidBefore',
            'amount',
            'paidAfter',
            'remaining'
        ))->setPaper('a5', 'portrait');

        $filename = 'nota-' . $customOrder->order_number . '-' . $payment->id . '.pdf';

        return $pdf->stream($filename);
    }

    private function authorizeOrder(CustomOrder $customOrder): void
    {
        $user = auth()->user();

        if ((int) $customOrder->tenant_id !== (int) $user->tenant_id) {
            abort(403);
        }

        if (
            $user->role?->name !== 'Owner' &&
            (int) $customOrder->branch_id !== (int) $user->branch_id
        ) {
            abort(403);
        }
    }
}
