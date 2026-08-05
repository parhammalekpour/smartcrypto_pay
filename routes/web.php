<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\KycController;


Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/dashboard', function () {
    if (auth()->user()->isMerchant()) {
        return redirect()->route('merchant.dashboard');
    } elseif (auth()->user()->isUser()) {
        return redirect()->route('user.dashboard');
    } elseif (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    } else {
        return view('dashboard');
    }
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/admin/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::get('/admin/kyc', [AdminController::class, 'kyc'])->name('admin.kyc');
    Route::post('/admin/kyc/{user}/approve', [AdminController::class, 'approveKyc'])->name('admin.kyc.approve');
    Route::post('/admin/kyc/{user}/reject', [AdminController::class, 'rejectKyc'])->name('admin.kyc.reject');
    Route::get('/admin/kyc/selfie/{user}', [KycController::class, 'adminSelfie'])->name('admin.kyc.selfie');
    Route::get('/admin/kyc/document/{user}/{filename}', [KycController::class, 'adminDocument'])->name('admin.kyc.document');
    Route::get('/admin/transactions', [AdminController::class, 'transactions'])->name('admin.transactions');
    Route::post('/admin/transactions/{transaction}/cancel', [AdminController::class, 'cancelTransaction'])->name('admin.transactions.cancel');

    // Admin tickets
    Route::get('/admin/tickets', [\App\Http\Controllers\AdminTicketController::class, 'index'])->name('admin.tickets.index');
    Route::get('/admin/tickets/{ticket}', [\App\Http\Controllers\AdminTicketController::class, 'show'])->name('admin.tickets.show');
    Route::get('/admin/tickets/{ticket}/messages', [\App\Http\Controllers\AdminTicketController::class, 'messages'])->name('admin.tickets.messages');
    Route::post('/admin/tickets/{ticket}/reply', [\App\Http\Controllers\AdminTicketController::class, 'reply'])->name('admin.tickets.reply');
    Route::post('/admin/tickets/{ticket}/close', [\App\Http\Controllers\AdminTicketController::class, 'close'])->name('admin.tickets.close');
});

Route::middleware(['auth', 'role:user', 'verified'])->group(function () {
    // Main Dashboard
    Route::get('/user', [WalletController::class, 'dashboard'])->name('user.dashboard');
    
    // Wallets
    Route::get('/user/wallets', [WalletController::class, 'wallets'])->name('user.wallets');
    Route::post('/user/wallets', [WalletController::class, 'storeWallet'])->name('user.wallets.store');
    // Delete a user wallet
    Route::delete('/user/wallets/{wallet}', [WalletController::class, 'destroy'])->name('user.wallets.destroy');
    
    // Send & Receive
    Route::get('/user/send', [WalletController::class, 'send'])->name('user.send');
    Route::get('/user/receive', [WalletController::class, 'receive'])->name('user.receive');

    // Tickets (user)
    Route::get('/tickets', [\App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [\App\Http\Controllers\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [\App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/messages', [\App\Http\Controllers\TicketController::class, 'messages'])->name('tickets.messages');
    Route::post('/tickets/{ticket}/message', [\App\Http\Controllers\TicketController::class, 'postMessage'])->name('tickets.message');

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

    // Two-Factor Authentication
    Route::get('/user/2fa', [\App\Http\Controllers\TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/user/2fa/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/user/2fa/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('2fa.disable');
    
    // Demo Deposit
    Route::post('/wallet/demo-deposit/{wallet}', [WalletController::class, 'demoDeposit']);
    
    // Transfer
    Route::post('/wallet/transfer', [WalletController::class, 'transfer'])->name('wallet.transfer');
});

// Public crypto prices endpoint (also aliased as /api/crypto-prices)
Route::get('/api/crypto-prices', [WalletController::class, 'getPrices'])->name('api.crypto-prices');

// Public crypto prices endpoint (no auth) - useful for client-side fetches that may not send cookies
Route::get('/public/crypto-prices', [WalletController::class, 'getPrices'])->name('public.api.crypto-prices');

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

    // KYC endpoints (upload and access user's own files)
    Route::post('/kyc/upload', [\App\Http\Controllers\KycController::class, 'store'])->name('kyc.upload');
    Route::get('/kyc/selfie', [\App\Http\Controllers\KycController::class, 'selfie'])->name('kyc.selfie');
    Route::get('/kyc/document/{filename}', [\App\Http\Controllers\KycController::class, 'document'])->name('kyc.document');
});

Route::middleware(['auth', 'role:merchant', 'verified'])->group(function () {
    // Main Dashboard
    Route::get('/merchant', [MerchantController::class, 'dashboard'])->name('merchant.dashboard');
    
    // Payments
    Route::get('/merchant/payments', [PaymentController::class, 'index'])->name('merchant.payments');
    Route::post('/merchant/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/merchant/payments/{id}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    
    // Wallets
    Route::get('/merchant/wallets', [MerchantController::class, 'wallets'])->name('merchant.wallets');
    Route::post('/merchant/wallets', [MerchantController::class, 'storeWallet'])->name('merchant.wallets.store');
    // Delete a merchant wallet
    Route::delete('/merchant/wallets/{wallet}', [MerchantController::class, 'destroyWallet'])->name('merchant.wallets.destroy');

    // Merchant: Send / Withdraw (allow merchants to send to external wallets)
    Route::get('/merchant/send', [MerchantController::class, 'send'])->name('merchant.send');
    Route::post('/merchant/send', [MerchantController::class, 'transfer'])->name('merchant.send.post');

    // Transactions
    Route::get('/merchant/transactions', [MerchantController::class, 'transactions'])->name('merchant.transactions');
    // Export transactions (CSV)
    Route::get('/merchant/transactions/export', [MerchantController::class, 'exportTransactions'])->name('merchant.transactions.export');
    // Download single transaction (summary / invoice-like)
    Route::get('/merchant/transactions/{transaction}/download', [MerchantController::class, 'downloadTransaction'])->name('merchant.transactions.download');
    
    // Invoices
    Route::get('/merchant/invoices', [MerchantController::class, 'invoices'])->name('merchant.invoices');
    // Download invoice (payment request) as downloadable HTML summary
    Route::get('/merchant/invoices/{invoice}/download', [MerchantController::class, 'downloadInvoice'])->name('merchant.invoices.download');
    
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

    // Two-Factor Authentication for merchants (allow merchants to manage 2FA like regular users)
    Route::get('/merchant/2fa', [\App\Http\Controllers\TwoFactorController::class, 'show'])->name('merchant.2fa.show');
    Route::post('/merchant/2fa/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('merchant.2fa.enable');
    Route::post('/merchant/2fa/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('merchant.2fa.disable');
    
    // API Keys
    // Route::get('/merchant/apikeys', [MerchantController::class, 'apikeys'])->name('merchant.apikeys');
});

Route::middleware(['auth', 'verified'])->group(function () {

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