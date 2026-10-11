<?php

namespace App\Http\Controllers;

use App\Models\JobDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JobDescriptionController extends Controller
{
    public function index()
    {
        $jobDescriptions = JobDescription::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->orderBy('name')->get();

        return view('job_descriptions.index', compact('jobDescriptions'));
    }

    public function create()
    {
        return view('job_descriptions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('job_descriptions', 'name')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],
        ]);

        JobDescription::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
        ]);

        return redirect()
            ->route('job-descriptions.index')
            ->with('success', 'Job description berhasil ditambahkan.');
    }

    public function edit(JobDescription $jobDescription)
    {
        $this->checkTenant($jobDescription);

        return view('job_descriptions.edit', compact('jobDescription'));
    }

    public function update(
        Request $request,
        JobDescription $jobDescription
    ) {
        $this->checkTenant($jobDescription);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('job_descriptions', 'name')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->ignore($jobDescription->id),
            ],
        ]);

        $jobDescription->update($data);

        return redirect()
            ->route('job-descriptions.index')
            ->with('success', 'Job description berhasil diperbarui.');
    }

    public function destroy(JobDescription $jobDescription)
    {
        $this->checkTenant($jobDescription);

        // Cegah penghapusan jika sudah digunakan dalam produksi.
        $used = DB::table('users_has_production_orders')
            ->where('job_description', $jobDescription->name)
            ->exists();

        if ($used) {
            return redirect()
                ->route('job-descriptions.index')
                ->with(
                    'error',
                    'Job description sudah digunakan dalam produksi sehingga tidak dapat dihapus.'
                );
        }

        $jobDescription->delete();

        return redirect()
            ->route('job-descriptions.index')
            ->with('success', 'Job description berhasil dihapus.');
    }

    private function checkTenant(JobDescription $jobDescription): void
    {
        if ($jobDescription->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }
    }
}