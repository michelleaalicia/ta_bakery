<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransQrisService
{
    private function url(): string
    {
        return config('services.midtrans.production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    private function request()
    {
        $key = config('services.midtrans.server_key');
        if (!$key) {
            throw new RuntimeException('Server Key Midtrans belum diatur.');
        }

        return Http::withBasicAuth($key, '')
            ->acceptJson()
            ->timeout(15);
    }

    public function charge(string $orderId, int $amount): array
    {
        $response = $this->request()->post($this->url() . '/v2/charge', [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'qris' => ['acquirer' => 'gopay'],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gagal membuat QRIS di Midtrans (HTTP ' . $response->status() . ').');
        }

        $data = $response->json();
        $actions = collect($data['actions'] ?? []);
        $qrUrl = $actions->firstWhere('name', 'generate-qr-code-v2')['url']
            ?? $actions->firstWhere('name', 'generate-qr-code')['url']
            ?? null;
        if (!$qrUrl || !str_starts_with($qrUrl, 'https://')) {
            throw new RuntimeException('Midtrans tidak mengembalikan URL QRIS yang valid.');
        }

        return ['transaction_id' => $data['transaction_id'] ?? null, 'qr_url' => $qrUrl];
    }

    public function status(string $orderId): array
    {
        $response = $this->request()->get($this->url() . '/v2/' . rawurlencode($orderId) . '/status');
        if (!$response->successful()) {
            throw new RuntimeException('Tidak dapat memeriksa status pembayaran Midtrans.');
        }
        return $response->json();
    }

    public function cancel(string $orderId): array
    {
        $response = $this->request()->post($this->url() . '/v2/' . rawurlencode($orderId) . '/cancel');
        if (!$response->successful()) {
            throw new RuntimeException('Midtrans menolak pembatalan (HTTP ' . $response->status() . '). Periksa status transaksi dahulu.');
        }
        return $response->json();
    }

    public function validSignature(array $payload): bool
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $field) {
            if (!isset($payload[$field]) || !is_scalar($payload[$field])) return false;
        }
        $secret = config('services.midtrans.server_key');
        if (!$secret) return false;
        $expected = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $secret);
        return hash_equals($expected, (string) $payload['signature_key']);
    }
}
