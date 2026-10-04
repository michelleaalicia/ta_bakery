<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;

class UsersImport implements ToModel
{
    public function model(array $row)
    {
        $tenantId = auth()->user()->tenant_id;

        $role = Role::where('name', $row[2])
            ->where('tenant_id', $tenantId)
            ->where('name', '!=', 'Owner')
            ->first();

        if (!$role) {
            throw new \Exception('Role "' . $row[2] . '" tidak ditemukan.');
        }

        $branch = Branch::where('name', $row[3])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$branch) {
            throw new \Exception('Cabang "' . $row[3] . '" tidak ditemukan.');
        }

        $password = Str::random(8);

        return new User([
            'tenant_id' => $tenantId,
            'name' => $row[0],
            'email' => $row[1],
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'wage_rate_per_hour' => $row[4],
            'password' => Hash::make($password),
            'status' => 'active',
        ]);
    }
}