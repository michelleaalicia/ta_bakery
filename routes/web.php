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
use App\Http\Controllers\JobDescriptionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductionScheduleController;
use App\Http\Controllers\CustomOrderController;
use App\Http\Controllers\CustomOrderPaymentController;
use App\Http\Controllers\CustomProductionController;
use App\Http\Controllers\JobCostSheetController;
use App\Http\Controllers\CustomOrderDeliveryController;
use App\Http\Controllers\SaleHistoryController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\GrossProfitReportController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;

Route::view('/', 'landing')->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Manajemen Pengguna
    Route::get('/users/import', [UserController::class, 'import'])
        ->middleware('module:Manajemen Pengguna')
        ->name('users.import');

    Route::post('/users/import', [UserController::class, 'importStore'])
        ->middleware('module:Manajemen Pengguna')
        ->name('users.import.store');

    Route::get('/users/import/template', [UserController::class, 'downloadTemplate'])
        ->middleware('module:Manajemen Pengguna')
        ->name('users.import.template');

    Route::resource('users', UserController::class)
        ->middleware('module:Manajemen Pengguna');

    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('module:Manajemen Pengguna')
        ->name('users.reset-password');

    Route::resource('job-descriptions', JobDescriptionController::class)
        ->except(['show'])
        ->middleware('module:Job Description');

    // Profil
    Route::get('/profile/password', [UserController::class, 'passwordEdit'])
        ->name('profile.password');

    Route::put('/profile/password', [UserController::class, 'passwordUpdate'])
        ->name('profile.password.update');

    Route::patch('/profile/name', [ProfileController::class, 'updateName'])
        ->name('profile.name.update');

    Route::patch('/profile/bakery', [ProfileController::class, 'updateBakery'])
        ->name('profile.bakery.update');

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

    Route::post(
        '/products/{product}/variants',
        [ProductVariantController::class, 'store']
    )
        ->middleware('module:Produk')
        ->name('product-variants.store');

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

    Route::post('/production_orders/{productionOrder}/apply-price', [ProductionOrderController::class, 'applyRecommendedPrice'])
        ->middleware('module:Produksi')
        ->name('production_orders.apply-price');

    Route::resource('production_orders', ProductionOrderController::class)
        ->middleware('module:Produksi');

    // POS

    Route::get('/pos', [PosController::class, 'index'])
        ->middleware('module:POS')
        ->name('pos.index');

    Route::post('/pos/select-branch', [PosController::class, 'selectBranch'])
        ->middleware('module:POS')
        ->name('pos.select-branch');

    Route::get('/pos/products', [PosController::class, 'products'])
        ->middleware('module:POS')
        ->name('pos.products');

    Route::post('/pos/checkout', [PosController::class, 'checkout'])
        ->middleware('module:POS')
        ->name('pos.checkout');

    Route::post('/pos/{sale}/confirm-payment', [PosController::class, 'confirmPayment'])
        ->middleware('module:POS')
        ->name('pos.confirm-payment');

    Route::post('/pos/{sale}/cancel-payment', [PosController::class, 'cancelPayment'])
        ->middleware('module:POS')
        ->name('pos.cancel-payment');

    // Jadwal Produksi
    Route::get('/production-schedules', [ProductionScheduleController::class, 'index'])
        ->middleware('module:Jadwal Produksi')
        ->name('production_schedules.index');

    Route::middleware('module:Custom Order')->group(function () {
        Route::get('/custom-orders', [CustomOrderController::class, 'index'])
            ->name('custom_orders.index');

        Route::get('/custom-orders/create', [CustomOrderController::class, 'create'])
            ->name('custom_orders.create');

        Route::post('/custom-orders', [CustomOrderController::class, 'store'])
            ->name('custom_orders.store');
    });

    Route::get('/custom-orders/{customOrder}', [CustomOrderController::class, 'show'])
        ->name('custom_orders.show');

    Route::patch('/custom-orders/{customOrder}/estimation', [CustomOrderController::class, 'updateEstimation'])
        ->name('custom_orders.estimation');

    Route::patch('/custom-orders/{customOrder}/price', [CustomOrderController::class, 'updatePrice'])
        ->name('custom_orders.price');

    Route::get('/custom-orders/{customOrder}/edit', [CustomOrderController::class, 'edit'])
        ->name('custom_orders.edit');

    Route::put('/custom-orders/{customOrder}', [CustomOrderController::class, 'update'])
        ->name('custom_orders.update');

    Route::patch('/custom-orders/{customOrder}/confirm', [CustomOrderController::class, 'confirm'])
        ->name('custom_orders.confirm');

    Route::middleware('module:Pembayaran')->group(function () {
        Route::get('/custom-order-payments', [CustomOrderPaymentController::class, 'index'])
            ->name('custom_order_payments.index');

        Route::get('/custom-order-payments/{customOrder}', [CustomOrderPaymentController::class, 'show'])
            ->name('custom_order_payments.show');

        Route::post('/custom-order-payments/{customOrder}', [CustomOrderPaymentController::class, 'store'])
            ->name('custom_order_payments.store');

        Route::post(
            '/custom-orders/{customOrder}/production',
            [CustomProductionController::class, 'store']
        )->name('custom_orders.production.store');

        Route::get('/custom-order-payments/{customOrder}/receipt/{payment}', [CustomOrderPaymentController::class, 'receipt'])
            ->name('custom_order_payments.receipt');
    });

    Route::put(
        '/production_orders/{productionOrder}/custom-actuals',
        [CustomProductionController::class, 'updateActuals']
    )->name('production_orders.custom-actuals.update');

    Route::get('/production_orders/{productionOrder}/job-cost-sheet', [JobCostSheetController::class, 'show'])
        ->middleware('module:Produksi')
        ->name('production_orders.job-cost-sheet');

    Route::patch('/custom-orders/{customOrder}/deliver', [CustomOrderDeliveryController::class, 'store'])
        ->middleware('module:Custom Order')
        ->name('custom_orders.deliver');


    Route::middleware('module:Riwayat Penjualan')->group(function () {
        Route::get('/sales', [SaleHistoryController::class, 'index'])
            ->name('sales.index');

        Route::get('/sales/{sale}', [SaleHistoryController::class, 'show'])
            ->name('sales.show');

        Route::get('/sales/{sale}/receipt', [SaleHistoryController::class, 'receipt'])
            ->name('sales.receipt');
    });

    Route::get('/reports/sales', [SalesReportController::class, 'index'])
        ->middleware('module:Laporan Penjualan')
        ->name('reports.sales');

    Route::get('/reports/gross-profit', [GrossProfitReportController::class, 'index'])
        ->middleware('module:Laporan Laba Rugi')
        ->name('reports.gross-profit');

    Route::get('/forecasts', [ForecastController::class, 'index'])
        ->middleware('module:Forecast')
        ->name('forecasts.index');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('module:Riwayat Aktivitas')
        ->name('audit_logs.index');
});

require __DIR__ . '/auth.php';