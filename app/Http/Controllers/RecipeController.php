<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\ProductVariant;
use App\Models\Ingredient;
use Illuminate\Http\Request;

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

        $ingredients = Ingredient::with('branch')
            ->whereHas('branch', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->get();

        return view('recipes.create', compact(
            'variants',
            'ingredients'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_variant_id' => [
                'required',
                'exists:product_variants,id'
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0'
            ],

            'unit' => [
                'required',
                'in:pcs,loyang,bungkus,box,toples,kg,gram,liter,botol'
            ],

            'steps' => [
                'required',
                'string'
            ],

            'ingredients' => [
                'required',
                'array',
                'min:1'
            ],

            'ingredients.*.ingredient_id' => [
                'required',
                'exists:ingredients,id'
            ],

            'ingredients.*.quantity' => [
                'required',
                'numeric',
                'gt:0'
            ],
        ]);

        $variant = ProductVariant::where('id', $request->product_variant_id)
            ->whereHas('product', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->whereDoesntHave('recipe')
            ->firstOrFail();

        $recipe = Recipe::create([
            'product_variant_id' => $variant->id,
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'steps' => $request->steps,
        ]);

        foreach ($request->ingredients as $item) {

            $ingredient = Ingredient::where('id', $item['ingredient_id'])
                ->whereHas('branch', function ($query) {
                    $query->where(
                        'tenant_id',
                        auth()->user()->tenant_id
                    );
                })
                ->firstOrFail();

            $recipe->ingredients()->attach(
                $ingredient->id,
                [
                    'quantity' => $item['quantity'],
                    'unit' => $ingredient->unit,
                ]
            );
        }

        return redirect()
            ->route('recipes.index')
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

        $ingredients = Ingredient::with('branch')
            ->whereHas('branch', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->get();

        return view('recipes.edit', compact(
            'recipe',
            'variants',
            'ingredients'
        ));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $this->checkTenant($recipe);

        $request->validate([
            'quantity' => [
                'required',
                'numeric',
                'gt:0'
            ],

            'unit' => [
                'required',
                'in:pcs,loyang,bungkus,box,toples,kg,gram,liter,botol'
            ],

            'steps' => [
                'required',
                'string'
            ],

            'ingredients' => [
                'required',
                'array',
                'min:1'
            ],

            'ingredients.*.ingredient_id' => [
                'required',
                'exists:ingredients,id'
            ],

            'ingredients.*.quantity' => [
                'required',
                'numeric',
                'gt:0'
            ],
        ]);

        $recipe->update([
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'steps' => $request->steps,
        ]);

        $recipe->ingredients()->detach();

        foreach ($request->ingredients as $item) {

            $ingredient = Ingredient::where('id', $item['ingredient_id'])
                ->whereHas('branch', function ($query) {
                    $query->where(
                        'tenant_id',
                        auth()->user()->tenant_id
                    );
                })
                ->firstOrFail();

            $recipe->ingredients()->attach(
                $ingredient->id,
                [
                    'quantity' => $item['quantity'],
                    'unit' => $ingredient->unit,
                ]
            );
        }

        return redirect()
            ->route('recipes.show', $recipe->id)
            ->with('success', 'Resep berhasil diperbarui.');
    }

    public function destroy(Recipe $recipe)
    {
        $this->checkTenant($recipe);

        $recipe->ingredients()->detach();
        $recipe->delete();

        return redirect()
            ->route('recipes.index')
            ->with('success', 'Resep berhasil dihapus.');
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