<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->get();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone_number' => ['required', 'string', 'max:45'],
        ]);

        Branch::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function show(Branch $branch)
    {
    }

    public function edit(Branch $branch)
    {
        $this->checkTenant($branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->checkTenant($branch);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone_number' => ['required', 'string', 'max:45'],
        ]);

        $branch->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        $this->checkTenant($branch);

        $branch->delete();

        return redirect()
            ->route('branches.index')
            ->with('success', 'Cabang berhasil dihapus.');
    }

    private function checkTenant(Branch $branch)
    {
        if ($branch->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }
    }
}