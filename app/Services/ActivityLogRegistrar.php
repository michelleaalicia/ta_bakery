<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CustomOrder;
use App\Models\Ingredient;
use App\Models\IngredientRestock;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;

/**
 * Catat perubahan operasional yang diizinkan; jangan catat kata sandi / kontak pelanggan.
 * Log aplikasi ini bukan immutable database audit trail.
 */
class ActivityLogRegistrar
{
    private const TRACKED = [
        Sale::class => ['branch_id', 'sale_date', 'total_amount', 'payment_method', 'payment_status'],
        CustomOrder::class => ['branch_id', 'order_number', 'product_name', 'quantity', 'selling_price', 'status', 'delivered_at'],
        ProductionOrder::class => ['branch_id', 'order_type', 'product_variant_id', 'quantity', 'status', 'hpp_per_unit'],
        Ingredient::class => ['branch_id', 'name', 'stock', 'unit_cost', 'min_stock'],
        IngredientRestock::class => ['ingredient_id', 'quantity', 'unit_price', 'total_cost', 'restock_date'],
        Product::class => ['name', 'status'],
    ];

    public static function boot(): void
    {
        foreach (self::TRACKED as $modelClass => $fields) {
            $modelClass::created(fn (Model $model) => self::record($model, 'created', $fields));
            $modelClass::updated(fn (Model $model) => self::record($model, 'updated', $fields));
            $modelClass::deleted(fn (Model $model) => self::record($model, 'deleted', $fields));
        }
    }

    private static function record(Model $model, string $event, array $fields): void
    {
        $actor = auth()->user();
        if (!$actor || !$actor->tenant_id) {
            return;
        }

        $branchId = $model->getAttribute('branch_id');
        $tenantId = $model->getAttribute('tenant_id');

        if ($model instanceof ProductionOrder) {
            $tenantId = $branchId ? Branch::whereKey($branchId)->value('tenant_id') : null;
        }

        // Restock tidak punya tenant_id dan branch_id sendiri: dapatkan dari bahan.
        if ($model instanceof IngredientRestock) {
            $ingredient = Ingredient::query()->whereKey($model->getAttribute('ingredient_id'))
                ->first(['id', 'tenant_id', 'branch_id']);
            if (!$ingredient) {
                return;
            }
            $tenantId = $ingredient->tenant_id;
            $branchId = $ingredient->branch_id;
        }

        if (!$tenantId || (int) $tenantId !== (int) $actor->tenant_id) {
            return;
        }

        $old = [];
        $new = [];
        foreach ($fields as $field) {
            if ($event === 'updated' && !$model->wasChanged($field)) {
                continue;
            }
            if ($event !== 'created') {
                $previous = method_exists($model, 'getPrevious') ? $model->getPrevious() : [];
                $old[$field] = $previous[$field] ?? $model->getRawOriginal($field);
            }
            if ($event !== 'deleted') {
                $new[$field] = $model->getAttribute($field);
            }
        }

        if ($event === 'updated' && !$new) {
            return;
        }

        AuditLog::create([
            'user_id' => $actor->id,
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'action' => $event,
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'old_values' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }
}
