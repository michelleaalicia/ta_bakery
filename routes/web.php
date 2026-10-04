<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\IngredientRestockController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProductionOrderController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Manajemen Pengguna
    Route::get('/users/import', [UserController::class, 'import'])
        ->middleware('module:Manajemen Pengguna')
        ->name('users.import');

    Route::post('/users/import', [UserController::class, 'importStore'])
        ->middleware('module:Manajemen Pengguna')
        ->name('users.import.store');

    Route::resource('users', UserController::class)
        ->middleware('module:Manajemen Pengguna');

    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('module:Manajemen Pengguna')
        ->name('users.reset-password');

    // Profil
    Route::get('/profile/password', [UserController::class, 'passwordEdit'])
        ->name('profile.password');

    Route::put('/profile/password', [UserController::class, 'passwordUpdate'])
        ->name('profile.password.update');

    // Role
    Route::resource('roles', RoleController::class)
        ->middleware('module:Manajemen Pengguna');

    // Cabang
    Route::resource('branches', BranchController::class)
        ->middleware('module:Cabang');

    // Kategori Produk
    Route::resource('categories', CategoryController::class)
        ->middleware('module:Kategori Produk');

    // Produk
    Route::resource('products', ProductController::class)
        ->middleware('module:Produk');

    Route::get('/products/{product}/variants/{variant}/edit', [ProductVariantController::class, 'edit'])
        ->middleware('module:Produk')
        ->name('product-variants.edit');

    Route::put('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])
        ->middleware('module:Produk')
        ->name('product-variants.update');

    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])
        ->middleware('module:Produk')
        ->name('product-variants.destroy');

    // Bahan Baku
    Route::resource('ingredients', IngredientController::class)
        ->middleware('module:Bahan Baku');

    Route::get('/ingredient-restocks/create', [IngredientRestockController::class, 'create'])
        ->middleware('module:Bahan Baku')
        ->name('ingredient-restocks.create');

    Route::post('/ingredient-restocks', [IngredientRestockController::class, 'store'])
        ->middleware('module:Bahan Baku')
        ->name('ingredient-restocks.store');

    // Resep
    Route::resource('recipes', RecipeController::class)
        ->middleware('module:Resep');

    // Produksi
    Route::put('/production_orders/{productionOrder}/status', [ProductionOrderController::class, 'updateStatus'])
        ->middleware('module:Produksi')
        ->name('production_orders.update-status');

    Route::resource('production_orders', ProductionOrderController::class)
        ->middleware('module:Produksi');
});

require __DIR__ . '/auth.php';