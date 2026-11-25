<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\MidtransQrisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransQrisController extends Controller
{
    public function notification(Request $request, MidtransQrisService $gateway, PosController $pos)
    {
        $data = $request->all();
        if (!$gateway->validSignature($data)) {
            return response()->json(['message' => 'Signature tidak valid'], 403);
        }
        $sale = Sale::where('midtrans_order_id', (string) $data['order_id'])->first();
        if (!$sale) return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);

        return $this->sync($sale, $data, $pos);
    }

    private function authorizeSale(Sale $sale): void
    {
        $user = auth()->user();
        if ((int) $sale->tenant_id !== (int) $user->tenant_id) abort(404);
        if (strcasecmp($user->role?->name ?? '', 'Owner') !== 0
            && (int) $sale->branch_id !== (int) $user->branch_id) abort(403);
        if ($sale->payment_method !== 'qris') abort(404);
    }

    private function syncFromProvider(Sale $sale, MidtransQrisService $gateway, PosController $pos)
    {
        $data = $gateway->status($sale->midtrans_order_id);
        return $this->sync($sale, $data, $pos);
    }

    public function status(Sale $sale, MidtransQrisService $gateway, PosController $pos)
    {
        $this->authorizeSale($sale);
        if (!$sale->midtrans_order_id) {
            return response()->json(['message' => 'Order ID Midtrans belum tersimpan pada transaksi lama. Hubungkan Order ID terlebih dahulu.'], 422);
        }

        if ($sale->payment_status === 'pending') {
            try {
                $result = $this->syncFromProvider($sale, $gateway, $pos);
                if ($result->getStatusCode() !== 200) return $result;
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => 'Tidak dapat menghubungi Midtrans. Coba lagi nanti.'], 502);
            }
        }

        $sale->refresh();
        return response()->json([
            'sale_id' => $sale->id,
            'payment_status' => $sale->payment_status,
            'total_amount' => (float) $sale->total_amount,
            'qr_url' => $sale->payment_status === 'pending' ? $sale->midtrans_qr_url : null,
        ]);
    }

    public function cancel(Sale $sale, MidtransQrisService $gateway, PosController $pos)
    {
        $this->authorizeSale($sale);
        if ($sale->payment_status !== 'pending') {
            return response()->json(['message' => 'Transaksi ini tidak lagi berstatus menunggu.'], 422);
        }
        if (!$sale->midtrans_order_id) {
            return response()->json(['message' => 'Hubungkan Order ID Midtrans transaksi lama sebelum membatalkannya.'], 422);
        }

        try {
            // Check latest provider status first; never cancel a payment already settled.
            $result = $this->syncFromProvider($sale, $gateway, $pos);
            if ($result->getStatusCode() !== 200) return $result;
            $sale->refresh();
            if ($sale->payment_status !== 'pending') {
                return response()->json(['message' => 'Status terbaru berhasil disinkronkan.', 'payment_status' => $sale->payment_status]);
            }

            $gateway->cancel($sale->midtrans_order_id);
            // Verify provider state, then apply locally. No local-only cancellation.
            $result = $this->syncFromProvider($sale, $gateway, $pos);
            if ($result->getStatusCode() !== 200) return $result;
            $sale->refresh();
            return response()->json([
                'message' => $sale->payment_status === 'cancelled'
                    ? 'Transaksi berhasil dibatalkan dan disinkronkan.'
                    : 'Permintaan pembatalan terkirim. Periksa status kembali.',
                'payment_status' => $sale->payment_status,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Pembatalan belum dapat diverifikasi di Midtrans. Klik Cek Status sebelum mencoba lagi.'], 502);
        }
    }

    public function attachLegacyOrder(Request $request, Sale $sale, MidtransQrisService $gateway, PosController $pos)
    {
        $this->authorizeSale($sale);
        if ($sale->payment_status !== 'pending' || $sale->midtrans_order_id) {
            return response()->json(['message' => 'Transaksi tidak memerlukan penghubungan Order ID.'], 422);
        }
        $input = $request->validate(['midtrans_order_id' => ['required', 'string', 'max:150']]);
        $orderId = trim($input['midtrans_order_id']);
        if (!preg_match('/^SIMB-' . preg_quote((string) $sale->id, '/') . '-[a-fA-F0-9]+$/D', $orderId)) {
            return response()->json(['message' => 'Order ID tidak sesuai dengan nomor penjualan ini.'], 422);
        }

        try {
            $data = $gateway->status($orderId);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Order ID tidak dapat diverifikasi ke Midtrans.'], 502);
        }
        if (($data['order_id'] ?? '') !== $orderId
            || ($data['payment_type'] ?? '') !== 'qris'
            || !is_numeric($data['gross_amount'] ?? null)
            || abs((float) $data['gross_amount'] - (float) $sale->total_amount) > 0.001
            || empty($data['transaction_id'])) {
            return response()->json(['message' => 'Detail transaksi Midtrans tidak cocok dengan transaksi ini.'], 422);
        }

        try {
            DB::transaction(function () use ($sale, $orderId, $data) {
                $locked = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
                if ($locked->payment_status !== 'pending' || $locked->midtrans_order_id) {
                    throw new \RuntimeException('Transaksi sudah berubah. Muat ulang halaman.');
                }
                $locked->update([
                    'midtrans_order_id' => $orderId,
                    'midtrans_transaction_id' => (string) $data['transaction_id'],
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal menghubungkan Order ID. Pastikan belum digunakan transaksi lain.'], 422);
        }
        $sale->refresh();
        $result = $this->sync($sale, $data, $pos);
        if ($result->getStatusCode() !== 200) return $result;
        $sale->refresh();
        return response()->json(['message' => 'Order ID berhasil dihubungkan dan status disinkronkan.', 'payment_status' => $sale->payment_status]);
    }

    private function sync(Sale $sale, array $data, PosController $pos)
    {
        if (($data['order_id'] ?? '') !== $sale->midtrans_order_id
            || ($data['payment_type'] ?? '') !== 'qris'
            || !is_numeric($data['gross_amount'] ?? null)
            || abs((float) $data['gross_amount'] - (float) $sale->total_amount) > 0.001
            || (isset($data['transaction_id']) && $sale->midtrans_transaction_id
                && $sale->midtrans_transaction_id !== (string) $data['transaction_id'])) {
            return response()->json(['message' => 'Identitas atau nominal tidak sesuai'], 422);
        }

        $status = (string) ($data['transaction_status'] ?? '');
        $fraud = strtolower((string) ($data['fraud_status'] ?? 'accept'));
        if (!in_array($status, ['pending', 'settlement', 'capture', 'expire', 'cancel', 'deny', 'failure'], true)) {
            return response()->json(['message' => 'Status tidak dikenali'], 422);
        }

        try {
            DB::transaction(function () use ($sale, $status, $fraud, $pos) {
                $locked = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
                if ($locked->payment_status !== 'pending') return;
                if (in_array($status, ['settlement'], true) && $fraud === 'accept') {
                    // Deduct only once, under the sale lock; failures roll back.
                    $pos->deductSaleStock($locked);
                    $locked->update(['payment_status' => 'paid']);
                } elseif (in_array($status, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $locked->update(['payment_status' => 'cancelled']);
                }
            }, 3);
        } catch (\Throwable $e) {
            Log::critical('QRIS paid but stock settlement needs attention', [
                'sale_id' => $sale->id, 'status' => $status, 'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Sinkronisasi stok gagal; perlu penanganan admin'], 503);
        }

        return response()->json(['message' => 'OK']);
    }
}
