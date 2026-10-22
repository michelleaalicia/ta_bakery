<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\ProductVariant;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with('productVariant.product')
            ->whereHas('productVariant.product', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->get();

        return view('recipes.index', compact('recipes'));
    }

    public function create()
    {
        $variants = ProductVariant::with('product')
            ->whereHas('product', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->whereDoesntHave('recipe')
            ->get();

        // Satu pilihan per bahan standar, meskipun tersedia di banyak cabang.
        $ingredients = $this->masterIngredients();

        return view('recipes.create', compact(
            'variants',
            'ingredients'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateRecipe($request);
        $variant = ProductVariant::whereKey($data['product_variant_id'])
            ->whereHas('product', fn($q) => $q->where('tenant_id', auth()->user()->tenant_id))
            ->whereDoesntHave('recipe')->firstOrFail();

        DB::transaction(function () use ($data, $variant) {
            $recipe = Recipe::create([
                'product_variant_id' => $variant->id,
                'quantity' => $data['quantity'],
                'unit' => $data['unit'],
                'production_time' => $data['production_time'],
                'steps' => $data['steps'],
            ]);
            $this->attachIngredients($recipe, $data['ingredients']);
        });

        return redirect()->route('recipes.index')
            ->with('success', 'Resep berhasil ditambahkan.');
    }

    public function show(Recipe $recipe)
    {
        $this->checkTenant($recipe);

        $recipe->load([
            'productVariant.product',
            'ingredients'
        ]);

        return view('recipes.show', compact('recipe'));
    }

    public function edit(Recipe $recipe)
    {
        $this->checkTenant($recipe);

        $recipe->load('ingredients');

        $variants = ProductVariant::with('product')
            ->whereHas('product', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->get();

        // Satu pilihan per bahan standar, meskipun tersedia di banyak cabang.
        $ingredients = $this->masterIngredients();

        return view('recipes.edit', compact(
            'recipe',
            'variants',
            'ingredients'
        ));
    }


    public function update(Request $request, Recipe $recipe)
    {
        $this->checkTenant($recipe);

        $data = $this->validateRecipe($request, true);

        $variant = ProductVariant::whereKey($data['product_variant_id'])
            ->whereHas('product', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->firstOrFail();

        $alreadyUsed = Recipe::where('product_variant_id', $variant->id)
            ->where('id', '!=', $recipe->id)
            ->exists();

        if ($alreadyUsed) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Varian ini sudah memiliki resep.',
            ]);
        }

        if ($recipe->product_variant_id != $variant->id) {
            $usedInProduction = \App\Models\ProductionOrder::where(
                'product_variant_id',
                $recipe->product_variant_id
            )->exists();

            if ($usedInProduction) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Produk atau varian tidak dapat diubah karena resep sudah pernah digunakan dalam produksi.',
                ]);
            }
        }

        DB::transaction(function () use ($recipe, $data, $variant) {
            $recipe->update([
                'product_variant_id' => $variant->id,
                'quantity' => $data['quantity'],
                'unit' => $data['unit'],
                'production_time' => $data['production_time'],
                'steps' => $data['steps'],
            ]);

            $recipe->ingredients()->detach();
            $this->attachIngredients($recipe, $data['ingredients']);
        });

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Resep berhasil diperbarui.');
    }

    public function destroy(Recipe $recipe)
    {
        $this->checkTenant($recipe);
        if (\App\Models\ProductionOrder::where('product_variant_id', $recipe->product_variant_id)->exists()) {
            return back()->with('error', 'Resep sudah pernah digunakan produksi dan tidak dapat dihapus.');
        }
        DB::transaction(function () use ($recipe) {
            $recipe->ingredients()->detach();
            $recipe->delete();
        });
        return redirect()->route('recipes.index')->with('success', 'Resep berhasil dihapus.');
    }

    private function masterIngredients()
    {
        // Pilih satu contoh record per master untuk menghindari duplikasi pilihan.
        return Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->whereNotNull('ingredient_master_id')
            ->orderBy('id')->get()->unique('ingredient_master_id')->values();
    }

    private function validateRecipe(Request $request, bool $editing = false): array
    {
        $rules = [
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['required', 'in:pcs,loyang,bungkus,box,toples,kg,gram,liter,botol'],
            'production_time' => ['required', 'numeric', 'gt:0'],
            'steps' => ['required', 'string'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
        if (!$editing) {
            $rules['product_variant_id'] = [
                'required',
                'integer',
                'exists:product_variants,id',
            ];
        }
        $data = $request->validate($rules);
        $ids = collect($data['ingredients'])->pluck('ingredient_id');
        $chosen = Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->whereIn('id', $ids)->get();
        if (
            $chosen->count() !== $ids->unique()->count()
            || $chosen->contains(fn($i) => !$i->ingredient_master_id)
            || $chosen->pluck('ingredient_master_id')->unique()->count() !== $ids->count()
        ) {
            throw ValidationException::withMessages([
                'ingredients' => 'Bahan tidak valid, milik bakery lain, atau dipilih lebih dari sekali.'
            ]);
        }
        return $data;
    }

    private function attachIngredients(Recipe $recipe, array $items): void
    {
        foreach ($items as $item) {
            $ingredient = Ingredient::where('tenant_id', auth()->user()->tenant_id)
                ->findOrFail($item['ingredient_id']);
            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $item['quantity'],
                'unit' => $ingredient->unit,
            ]);
        }
    }

    private function checkTenant(Recipe $recipe)
    {
        if (
            $recipe->productVariant->product->tenant_id
            != auth()->user()->tenant_id
        ) {
            abort(403);
        }
    }
}