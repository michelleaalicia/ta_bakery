<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class ProductionScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isOwner = $user->role?->name === 'Owner';

        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $date = $data['date'] ?? now()->toDateString();

        $branches = Branch::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        $branchId = null;

        if ($isOwner && !empty($data['branch_id'])) {
            $branch = $branches->firstWhere('id', $data['branch_id']);

            if (!$branch) {
                abort(403);
            }

            $branchId = $branch->id;
        }

        $query = ProductionOrder::with([
            'productVariant.product',
            'customOrder',
            'branch',
            'users' => function ($query) use ($isOwner, $user) {
                if (!$isOwner) {
                    $query->where('users.id', $user->id);
                }
            }
        ])
            ->where(function ($query) use ($user) {
                $query->where(function ($regular) use ($user) {
                    $regular->where('order_type', 'regular')
                        ->whereHas('productVariant.product', function ($product) use ($user) {
                            $product->where('tenant_id', $user->tenant_id);
                        });
                })->orWhere(function ($custom) use ($user) {
                    $custom->where('order_type', 'custom')
                        ->whereHas('customOrder', function ($order) use ($user) {
                            $order->where('tenant_id', $user->tenant_id);
                        });
                });
            })
            ->whereDate('production_date', $date);

        if ($isOwner) {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        } else {
            $query->where('branch_id', $user->branch_id)
                ->whereHas('users', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                });
        }

        $schedules = $query->orderBy('id')->get();

        return view('production_schedules.index', compact(
            'schedules',
            'branches',
            'date',
            'branchId',
            'isOwner'
        ));
    }
}
