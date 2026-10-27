<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function updateName(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role?->name === 'Owner', 403);

        $data = $request->validate([
            'profile_name' => ['required', 'string', 'max:255'],
        ]);

        $user->update(['name' => trim($data['profile_name'])]);

        return redirect()->route('profile.password')
            ->with('success', 'Nama pengguna berhasil diperbarui.');
    }

    public function updateBakery(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role?->name === 'Owner', 403);

        $data = $request->validate([
            'bakery_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = Tenant::whereKey($user->tenant_id)->firstOrFail();
        $tenant->update([
            'name' => trim($data['bakery_name']),
            'phone_number' => $data['phone_number'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        return redirect()->route('profile.password')
            ->with('success', 'Informasi bakery berhasil diperbarui.');
    }
}
