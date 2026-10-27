<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $tenantId = auth()->user()->tenant_id;

        $name = trim($row['nama'] ?? '');
        $email = trim($row['email'] ?? '');
        $roleName = trim($row['role'] ?? '');
        $branchName = trim($row['cabang_opsional'] ?? '');
        // Header 'Tarif Upah/Jam' dinormalisasi Laravel Excel menjadi 'tarif_upahjam'.
        // Terima juga variasi header lama supaya template tetap kompatibel.
        $wage = $row['tarif_upahjam'] ?? $row['tarif_upah_jam'] ?? null;

        if ($name === '' || $email === '' || $roleName === '') {
            throw new \Exception('Nama, Email, dan Role wajib diisi.');
        }

        if (User::where('email', $email)->exists()) {
            throw new \Exception('Email ' . $email . ' sudah digunakan.');
        }

        if (!is_numeric($wage) || $wage < 0) {
            throw new \Exception('Tarif Upah/Jam untuk ' . $name . ' harus berupa angka minimal 0.');
        }

        $role = Role::where('name', $roleName)
            ->where('tenant_id', $tenantId)
            ->where('name', '!=', 'Owner')
            ->first();

        if (!$role) {
            throw new \Exception('Role "' . $roleName . '" tidak ditemukan.');
        }

        $branchId = null;
        $hasBranches = Branch::where('tenant_id', $tenantId)->exists();

        if ($hasBranches) {
            if ($branchName === '') {
                throw new \Exception('Cabang wajib diisi untuk pengguna ' . $name . '.');
            }

            $branch = Branch::where('name', $branchName)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$branch) {
                throw new \Exception('Cabang "' . $branchName . '" tidak ditemukan.');
            }

            $branchId = $branch->id;
        }

        return new User([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'role_id' => $role->id,
            'branch_id' => $branchId,
            'wage_rate_per_hour' => $wage,
            'password' => Hash::make(Str::random(8)),
            'status' => 'active',
        ]);
    }
}
