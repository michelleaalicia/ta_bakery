<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private const HIDDEN_MODULE = 'Job Description';

    private function visibleModules()
    {
        return Module::query()->where('name', '!=', self::HIDDEN_MODULE)->orderBy('id')->get();
    }

    private function validateRole(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:45'],
            'modules' => ['nullable', 'array'],
            'modules.*' => [
                'integer',
                'distinct',
                Rule::exists('modules', 'id')->where(function ($q) {
                    $q->where('name', '!=', self::HIDDEN_MODULE);
                })
            ],
        ]);
    }

    public function index()
    {
        $roles = Role::where('tenant_id', auth()->user()->tenant_id)
            ->where('name', '!=', 'Owner')
            ->with('modules')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $modules = $this->visibleModules();
        return view('roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);

        $role = Role::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
        ]);
        $role->modules()->sync($data['modules'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(Role $role)
    {
        if ($role->tenant_id != auth()->user()->tenant_id || $role->name === 'Owner') {
            abort(403);
        }
        $modules = $this->visibleModules();
        $role->load('modules');
        return view('roles.edit', compact('role', 'modules'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->tenant_id != auth()->user()->tenant_id || $role->name === 'Owner') {
            abort(403);
        }
        $data = $this->validateRole($request);

        // Hak akses Job Description yang sudah ada tidak dicabut diam-diam
        // hanya karena opsinya disembunyikan di formulir Role.
        $hiddenIds = $role->modules()->where('name', self::HIDDEN_MODULE)
            ->pluck('modules.id')->all();
        $selectedIds = array_unique(array_merge($data['modules'] ?? [], $hiddenIds));

        $role->update(['name' => $data['name']]);
        $role->modules()->sync($selectedIds);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if ($role->tenant_id != auth()->user()->tenant_id || $role->name === 'Owner') {
            abort(403);
        }
        $role->modules()->detach();
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
