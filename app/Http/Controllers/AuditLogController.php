<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('role');
        $isOwner = strcasecmp($user->role?->name ?? '', 'Owner') === 0;
        $tenantId = (int) $user->tenant_id;

        $data = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'action' => ['nullable', Rule::in(['created', 'updated', 'deleted'])],
            'table' => ['nullable', Rule::in(['sales', 'custom_orders', 'production_orders', 'ingredients', 'products'])],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branches = Branch::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $branchId = $isOwner ? ($data['branch_id'] ?? null) : $user->branch_id;

        if (!$isOwner && !$branchId) {
            abort(403, 'Akun belum memiliki cabang.');
        }
        if ($branchId && !$branches->contains('id', (int) $branchId)) {
            abort(403);
        }

        $logs = AuditLog::query()
            ->with(['user:id,name', 'branch:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(!empty($data['start_date']), fn ($q) => $q->whereDate('created_at', '>=', $data['start_date']))
            ->when(!empty($data['end_date']), fn ($q) => $q->whereDate('created_at', '<=', $data['end_date']))
            ->when(!empty($data['action']), fn ($q) => $q->where('action', $data['action']))
            ->when(!empty($data['table']), fn ($q) => $q->where('table_name', $data['table']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('audit_logs.index', compact('logs', 'branches', 'isOwner', 'branchId'));
    }
}
