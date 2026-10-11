<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\Module;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:45'],
            'address' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name' => $validated['tenant_name'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
            ]);

            $role = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'role_id' => $role->id,
                'branch_id' => null,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);

            $modules = Module::pluck('id');
            $role->modules()->attach($modules);

            return $user;
        });

        Auth::login($user);

        event(new Registered($user));

        return redirect()
            ->route('verification.notice')
            ->with('status', 'verification-link-sent');
    }
}
