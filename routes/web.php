<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\NotificationController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (auth()->user()->isMerchant()) {
        return redirect()->route('merchant.dashboard');
    } elseif (auth()->user()->isUser()) {
        return redirect()->route('user.dashboard');
    } else {
        return view('dashboard');
    }
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin', function () {
    return view('admin');
})->middleware(['auth', 'role:admin']);

Route::middleware(['auth', 'role:user'])->group(function () {
    // Main Dashboard
    Route::get('/user', [WalletController::class, 'dashboard'])->name('user.dashboard');
    
    // Wallets
    Route::get('/user/wallets', [WalletController::class, 'wallets'])->name('user.wallets');
    Route::post('/user/wallets', [WalletController::class, 'storeWallet'])->name('user.wallets.store');
    
    // Send & Receive
    Route::get('/user/send', [WalletController::class, 'send'])->name('user.send');
    Route::get('/user/receive', [WalletController::class, 'receive'])->name('user.receive');
    
    // Transactions
    Route::get('/user/transactions', [WalletController::class, 'transactions'])->name('user.transactions');
    Route::get('/user/pending-payments', [WalletController::class, 'pendingPayments'])->name('user.pending-payments');
    Route::post('/payment-request/{id}/reject', [WalletController::class, 'rejectPayment'])->name('payment-request.reject');
    
    // Old routes (keeping for backward compatibility)
    Route::get('/user/transfer', [WalletController::class, 'showTransfer'])->name('wallet.transfer.show');
    Route::get('/user/payments', [WalletController::class, 'showPayments'])->name('wallet.payments.show');
    
    // Settings
    Route::get('/user/settings', [WalletController::class, 'settings'])->name('user.settings');
    Route::patch('/user/settings', [WalletController::class, 'updateSettings'])->name('settings.update');
    Route::post('/logout-all-devices', [WalletController::class, 'logoutAllDevices'])->name('logout-all-devices');
    
    // Demo Deposit
    Route::post('/wallet/demo-deposit/{wallet}', [WalletController::class, 'demoDeposit']);
    
    // Transfer
    Route::post('/wallet/transfer', [WalletController::class, 'transfer'])->name('wallet.transfer');
    
    // Crypto Prices
    Route::get('/api/crypto-prices', [WalletController::class, 'getPrices'])->name('api.crypto-prices');
});

// Notifications - Available for both users and merchants
Route::middleware('auth')->group(function () {
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.get');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/{id}/delete', [NotificationController::class, 'deleteNotification'])->name('notifications.delete');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    
    // Auto-refresh polling endpoint
    Route::get('/api/refresh-status', [NotificationController::class, 'checkRefreshStatus'])->name('api.refresh-status');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:merchant'])->group(function () {
    // Main Dashboard
    Route::get('/merchant', [MerchantController::class, 'dashboard'])->name('merchant.dashboard');
    
    // Payments
    Route::get('/merchant/payments', [PaymentController::class, 'index'])->name('merchant.payments');
    Route::post('/merchant/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/merchant/payments/{id}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    
    // Wallets
    Route::get('/merchant/wallets', [MerchantController::class, 'wallets'])->name('merchant.wallets');
    Route::post('/merchant/wallets', [MerchantController::class, 'storeWallet'])->name('merchant.wallets.store');
    
    // Transactions
    Route::get('/merchant/transactions', [MerchantController::class, 'transactions'])->name('merchant.transactions');
    
    // Invoices
    Route::get('/merchant/invoices', [MerchantController::class, 'invoices'])->name('merchant.invoices');
    
    // Settlements
    Route::get('/merchant/settlements', [MerchantController::class, 'settlements'])->name('merchant.settlements');

    // Customers
    Route::get('/merchant/customers', [MerchantController::class, 'customers'])->name('merchant.customers');
    Route::post('/merchant/customers', [MerchantController::class, 'storeCustomer'])->name('merchant.customers.store');
    Route::get('/merchant/customers/{customer}/edit', [MerchantController::class, 'editCustomer'])->name('merchant.customers.edit');
    Route::put('/merchant/customers/{customer}', [MerchantController::class, 'updateCustomer'])->name('merchant.customers.update');
    Route::delete('/merchant/customers/{customer}', [MerchantController::class, 'destroyCustomer'])->name('merchant.customers.destroy');
    Route::get('/merchant/customers/{customer}', [MerchantController::class, 'showCustomer'])->name('merchant.customers.show');
    
    // Settings
    Route::get('/merchant/settings', [MerchantController::class, 'settings'])->name('merchant.settings');
    Route::patch('/merchant/settings', [MerchantController::class, 'updateMerchantSettings'])->name('merchant.settings.update');
    Route::put('/merchant/settings', [MerchantController::class, 'updateMerchantSettings']);
    
    // API Keys
    // Route::get('/merchant/apikeys', [MerchantController::class, 'apikeys'])->name('merchant.apikeys');
});

Route::middleware('auth')->group(function () {

    Route::get(
        '/pay/{token}',
        [PaymentController::class, 'show']
    )->name('payment.show');

    Route::post(
        '/pay/{token}',
        [PaymentController::class, 'pay']
    )->name('payment.pay');
});
    
require __DIR__.'/auth.php';