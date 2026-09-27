<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:45'],
        ]);

        Category::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function show(Category $category)
    {
        $this->checkTenant($category);

        return view('categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $this->checkTenant($category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->checkTenant($category);

        $request->validate([
            'name' => ['required', 'string', 'max:45'],
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $this->checkTenant($category);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    private function checkTenant(Category $category)
    {
        if ($category->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }
    }
}