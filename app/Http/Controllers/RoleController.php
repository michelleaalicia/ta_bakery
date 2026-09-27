<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Module;
use Illuminate\Http\Request;

class RoleController extends Controller
{
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
        $modules = Module::all();

        return view('roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:45'],
            'modules' => ['nullable', 'array'],
        ]);

        $role = Role::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
        ]);

        $role->modules()->sync($request->modules ?? []);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(Role $role)
    {
        if ($role->tenant_id != auth()->user()->tenant_id || $role->name === 'Owner') {
            abort(403);
        }

        $modules = Module::all();

        return view('roles.edit', compact('role', 'modules'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->tenant_id != auth()->user()->tenant_id || $role->name === 'Owner') {
            abort(403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:45'],
            'modules' => ['nullable', 'array'],
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        $role->modules()->sync($request->modules ?? []);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if ($role->tenant_id != auth()->user()->tenant_id || $role->name === 'Owner') {
            abort(403);
        }

        $role->modules()->detach();
        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}