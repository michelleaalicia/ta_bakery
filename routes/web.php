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




Route::get('/', function () {
    return redirect()->route('login');
});


Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');


    // Manajemen Pengguna
    Route::resource('users', UserController::class)
        ->middleware('module:Manajemen Pengguna');


    // Role
    Route::resource('roles', RoleController::class)
        ->middleware('module:Manajemen Pengguna');


    // Cabang
    Route::resource('branches', BranchController::class)
        ->middleware('module:Cabang');

    Route::resource('categories', CategoryController::class)
        ->middleware('module:Kategori Produk');

    Route::resource('products', ProductController::class)
        ->middleware('module:Produk');

    Route::resource('ingredients', IngredientController::class)
        ->middleware('module:Bahan Baku');

    Route::get('/ingredient-restocks/create', [IngredientRestockController::class, 'create'])
        ->middleware('module:Bahan Baku')
        ->name('ingredient-restocks.create');

    Route::post('/ingredient-restocks', [IngredientRestockController::class, 'store'])
        ->middleware('module:Bahan Baku')
        ->name('ingredient-restocks.store');

    Route::resource('recipes', RecipeController::class)
        ->middleware('module:Resep');
});


require __DIR__ . '/auth.php';