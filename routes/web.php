<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\OrderChatController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderNotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        foreach (['services', 'materials'] as $type) {
            Route::get('/'.$type, [MasterDataController::class, 'index'])->name($type.'.index');
            Route::get('/'.$type.'/create', [MasterDataController::class, 'create'])->name($type.'.create');
            Route::post('/'.$type, [MasterDataController::class, 'store'])->name($type.'.store');
            Route::get('/'.$type.'/{id}/edit', [MasterDataController::class, 'edit'])->whereNumber('id')->name($type.'.edit');
            Route::put('/'.$type.'/{id}', [MasterDataController::class, 'update'])->whereNumber('id')->name($type.'.update');
        }
    });
    Route::get('/pelanggan/catalog', [CatalogController::class, 'index'])->middleware('role:pelanggan')->name('pelanggan.catalog');
    Route::middleware('role:pelanggan')->prefix('pelanggan/orders')->name('pelanggan.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/print', [OrderController::class, 'receipt'])->name('print');
        Route::get('/{order}/design', [OrderController::class, 'design'])->name('design');
        Route::post('/{order}/approve', [OrderController::class, 'approve'])->name('approve');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::post('/{order}/revise', [OrderController::class, 'revise'])->name('revise');
    });
    Route::middleware('role:admin')->prefix('admin/orders')->name('admin.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/print', [OrderController::class, 'receipt'])->name('print');
        Route::get('/{order}/design', [OrderController::class, 'design'])->name('design');
        Route::post('/{order}/review', [OrderController::class, 'review'])->name('review');
        Route::post('/{order}/notifications', [OrderNotificationController::class, 'store'])->middleware('throttle:30,1')->name('notifications.store');
        Route::post('/{order}/progress', [OrderController::class, 'progress'])->name('progress');
    });
    Route::middleware('role:pelanggan')->prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof');
    });
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof');
        Route::post('/payments/{payment}/review', [PaymentController::class, 'review'])->name('payments.review');
    });
    foreach (['admin', 'pelanggan'] as $chatRole) {
        Route::middleware('role:'.$chatRole)->prefix($chatRole.'/chat')->name($chatRole.'.chat.')->group(function () {
            Route::get('/', [OrderChatController::class, 'index'])->name('index');
            Route::get('/{order}', [OrderChatController::class, 'show'])->name('show');
            Route::get('/{order}/feed', [OrderChatController::class, 'feed'])->name('feed');
            Route::post('/{order}', [OrderChatController::class, 'store'])->middleware('throttle:30,1')->name('store');
        });
    }
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', function () {
        $role = request()->user()->role;
        abort_unless(in_array($role, ['admin', 'manajer', 'pelanggan'], true), 403);

        return redirect()->route($role.'.dashboard');
    })->name('dashboard');
    foreach (['admin', 'manajer', 'pelanggan'] as $role) {
        Route::get('/'.$role.'/dashboard', fn () => view('dashboard', ['role' => request()->user()->role]))
            ->middleware('role:'.$role)->name($role.'.dashboard');
    }
});
