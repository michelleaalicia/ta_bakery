<?php

namespace App\Http\Controllers;

use App\Models\CustomOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomOrderDeliveryController extends Controller
{
    public function store(Request $request, CustomOrder $customOrder)
    {
        $user = $request->user();

        if ((int) $customOrder->tenant_id !== (int) $user->tenant_id) {
            abort(403);
        }

        if ($user->role?->name !== 'Owner' && (int) $customOrder->branch_id !== (int) $user->branch_id) {
            abort(403);
        }

        if (!in_array($user->role?->name, ['Owner', 'Kasir'], true)) {
            abort(403);
        }

        DB::transaction(function () use ($customOrder) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === 'delivered' || $order->delivered_at !== null) {
                throw ValidationException::withMessages([
                    'delivery' => 'Pesanan sudah pernah diserahkan.',
                ]);
            }

            if ($order->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'delivery' => 'Hanya pesanan terkonfirmasi yang dapat diserahkan.',
                ]);
            }

            $production = $order->productionOrder;

            if (!$production || $production->order_type !== 'custom' || $production->status !== 'completed') {
                throw ValidationException::withMessages([
                    'delivery' => 'Produksi Custom harus selesai sebelum pesanan diserahkan.',
                ]);
            }

            $total = (float) $order->selling_price;
            $paid = (float) $order->payments()->sum('amount');

            if ($total <= 0 || round($total - $paid, 2) > 0) {
                throw ValidationException::withMessages([
                    'delivery' => 'Pembayaran belum lunas. Lunasi sisa tagihan sebelum menyerahkan pesanan.',
                ]);
            }

            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        });

        return redirect()
            ->route('custom_orders.show', $customOrder)
            ->with('success', 'Pesanan berhasil diserahkan kepada pelanggan.');
    }
}
