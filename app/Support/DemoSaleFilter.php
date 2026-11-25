<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Kecualikan transaksi khusus simulasi DES dari laporan operasional.
 * HANYA berlaku untuk database ta_bakery_test.
 * Forecast tetap membaca transaksi simulasi untuk demonstrasi algoritma.
 */
final class DemoSaleFilter
{
    public static function exclude(Builder $query): Builder
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database !== 'ta_bakery_test') {
            return $query;
        }

        return $query->where(function (Builder $sales) {
            $sales->whereNull('midtrans_order_id')
                ->orWhere('midtrans_order_id', 'not like', 'DEMO-DES-%');
        });
    }
}
