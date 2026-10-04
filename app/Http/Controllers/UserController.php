<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'branch'])
            ->where('tenant_id', auth()->user()->tenant_id);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->get();

        $roles = Role::where('tenant_id', auth()->user()->tenant_id)
            ->where('name', '!=', 'Owner')
            ->get();

        return view('users.index', compact('users', 'roles'));
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

    public function import()
    {
        $tenantId = auth()->user()->tenant_id;

        $roles = Role::where('tenant_id', $tenantId)
            ->where('name', '!=', 'Owner')
            ->get();

        $branches = Branch::where('tenant_id', $tenantId)->get();

        return view('users.import', compact('roles', 'branches'));
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'max:2048'],
        ]);

        Excel::import(new UsersImport, $request->file('file'));

        return redirect()
            ->route('users.index')
            ->with('success', 'Data karyawan berhasil diimport.');
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->merge([
            'wage_rate_per_hour' => $request->wage_rate_per_hour,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'wage_rate_per_hour' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $password = Str::random(8);

        $role = Role::where('id', $request->role_id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $branch = Branch::where('id', $request->branch_id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $user = User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'wage_rate_per_hour' => str_replace('.', '', $request->wage_rate_per_hour),
            'status' => $request->status,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil ditambahkan. Password awal: ' . $password);
    }

    public function passwordEdit()
    {
        return view('profile.password');
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('profile.password')
            ->with('success', 'Password berhasil diubah.');
    }

    public function resetPassword(User $user)
    {
        if ($user->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }

        if ($user->role?->name === 'Owner') {
            abort(403);
        }

        $password = \Illuminate\Support\Str::random(8);

        $user->update([
            'password' => Hash::make($password),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Password baru untuk ' . $user->name . ': ' . $password);
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
        $tenantId = auth()->user()->tenant_id;

        $user = User::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($user->role?->name === 'Owner') {
            abort(403);
        }

        $roles = Role::where('tenant_id', $tenantId)
            ->where('name', '!=', 'Owner')
            ->get();

        $branches = Branch::where('tenant_id', $tenantId)->get();

        return view('users.edit', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $user = User::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($user->role?->name === 'Owner') {
            abort(403);
        }

        $request->merge([
            'wage_rate_per_hour' => str_replace('.', '', $request->wage_rate_per_hour),
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'wage_rate_per_hour' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $role = Role::where('id', $request->role_id)
            ->where('tenant_id', $tenantId)
            ->where('name', '!=', 'Owner')
            ->firstOrFail();

        $branch = Branch::where('id', $request->branch_id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'wage_rate_per_hour' => $request->wage_rate_per_hour,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $user = User::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($user->role?->name === 'Owner') {
            abort(403);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}
