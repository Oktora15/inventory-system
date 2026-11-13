<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarcodeScannerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductLabelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'landing'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('role:admin,warehouse')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/reports/inventory', InventoryReportController::class)->name('reports.inventory');
        Route::get('/reports/inventory/products.csv', [InventoryReportController::class, 'productsCsv'])
            ->name('reports.inventory.products-csv');
        Route::get('/reports/inventory/movements.csv', [InventoryReportController::class, 'movementsCsv'])
            ->name('reports.inventory.movements-csv');
        Route::get('/reports/inventory/excel', [InventoryReportController::class, 'excel'])
            ->name('reports.inventory.excel');
        Route::get('/reports/inventory/pdf', [InventoryReportController::class, 'pdf'])
            ->name('reports.inventory.pdf');
        Route::get('/reports/inventory/print', [InventoryReportController::class, 'print'])
            ->name('reports.inventory.print');
        Route::get('/labels', [ProductLabelController::class, 'index'])->name('labels.index');
        Route::post('/labels/print', [ProductLabelController::class, 'preview'])->name('labels.print');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::resource('categories', CategoryController::class)
            ->except(['index', 'show'])
            ->middleware('role:admin');
    });

    Route::get('/scanner', [BarcodeScannerController::class, 'index'])->name('scanner.index');
    Route::post('/scanner/lookup', [BarcodeScannerController::class, 'lookup'])
        ->middleware('throttle:30,1')
        ->name('scanner.lookup');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::middleware('role:admin,warehouse')->group(function () {
        Route::resource('products', ProductController::class)
            ->except(['index', 'show']);
    });
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('/stock-transactions', [StockTransactionController::class, 'index'])
        ->name('stock-transactions.index');
    Route::get('/stock-transactions/create', [StockTransactionController::class, 'create'])
        ->middleware('role:admin,warehouse,cashier')
        ->name('stock-transactions.create');
    Route::post('/stock-transactions', [StockTransactionController::class, 'store'])
        ->middleware('role:admin,warehouse,cashier')
        ->name('stock-transactions.store');

    Route::middleware('role:admin')->group(function () {
        Route::post('/stock-transactions/{stockTransaction}/reverse', [StockTransactionController::class, 'reverse'])
            ->name('stock-transactions.reverse');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])
            ->name('users.deactivate');
        Route::patch('/users/{user}/activate', [UserManagementController::class, 'activate'])
            ->name('users.activate');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
            ->name('users.destroy');
        Route::post('/users/{user}/password-reset', [UserManagementController::class, 'sendPasswordResetLink'])
            ->middleware('throttle:6,1')
            ->name('users.password-reset');
    });
});
