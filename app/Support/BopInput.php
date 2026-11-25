<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BopInput
{
    public static function prepare(Request $request, string $totalField = 'bop_cost'): array
    {
        $mode = $request->input('bop_mode', 'total');
        if (!in_array($mode, ['total', 'detailed'], true)) {
            throw ValidationException::withMessages(['bop_mode' => 'Pilih jenis input BOP.']);
        }
        if ($mode === 'total') {
            $raw = (string) $request->input($totalField, '');
            $amount = str_replace('.', '', $raw);
            // Form Rupiah memakai angka bulat dengan pemisah ribuan titik.
            if (!preg_match('/^\d+$/', $amount) || (float) $amount > 9999999999999) {
                throw ValidationException::withMessages([$totalField => 'Total BOP harus berupa nominal Rupiah yang valid.']);
            }
            $request->merge([$totalField => $amount]);
            return ['mode' => 'total', 'total' => (float) $amount, 'items' => []];
        }

        $rows = $request->input('bop_items', []);
        if (!is_array($rows) || count($rows) < 1 || count($rows) > 50) {
            throw ValidationException::withMessages(['bop_items' => 'Tambahkan 1 sampai 50 rincian BOP.']);
        }
        $categories = ['Penyusutan Peralatan', 'Transportasi / Ongkir', 'Kemasan / Packaging', 'Listrik', 'Gas', 'Air', 'Pemeliharaan Peralatan', 'Lainnya'];
        $items = [];
        $total = 0;
        foreach (array_values($rows) as $i => $row) {
            if (!is_array($row) || !in_array($row['category'] ?? null, $categories, true)) {
                throw ValidationException::withMessages(['bop_items' => 'Kategori rincian BOP tidak valid pada baris ' . ($i + 1) . '.']);
            }
            $raw = str_replace('.', '', (string) ($row['amount'] ?? ''));
            if (!preg_match('/^\d+$/', $raw)) {
                throw ValidationException::withMessages(['bop_items' => 'Nominal rincian harus angka bulat pada baris ' . ($i + 1) . '.']);
            }
            $amount = (float) $raw;
            $total += $amount;
            $items[] = ['category' => $row['category'], 'amount' => $amount];
        }
        if ($total > 9999999999999) {
            throw ValidationException::withMessages(['bop_items' => 'Total rincian BOP terlalu besar.']);
        }
        $request->merge([$totalField => (string) $total]);
        return ['mode' => 'detailed', 'total' => $total, 'items' => $items];
    }

    public static function save(string $ownerColumn, int $ownerId, array $prepared): void
    {
        DB::table('bop_details')->where($ownerColumn, $ownerId)->delete();
        foreach ($prepared['items'] as $item) {
            DB::table('bop_details')->insert(array_merge($item, [
                $ownerColumn => $ownerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
