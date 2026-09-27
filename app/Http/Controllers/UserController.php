<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'branch'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $tenantId = auth()->user()->tenant_id;

        $roles = Role::where('tenant_id', $tenantId)
            ->where('name', '!=', 'Owner')
            ->get();

        $branches = Branch::where('tenant_id', $tenantId)->get();

        return view('users.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $role = Role::where('id', $request->role_id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $branch = Branch::where('id', $request->branch_id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
