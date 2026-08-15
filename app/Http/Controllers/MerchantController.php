<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\PaymentRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Notification;
use App\Services\CryptoPrice;
use App\Services\EthereumService;

class MerchantController extends Controller
{
    public function dashboard()
    {
        $wallets = auth()->user()->wallets ?? collect();

        $cryptoPrice = new CryptoPrice();
        $totalRevenue = $wallets->reduce(function ($sum, $wallet) use ($cryptoPrice) {
            return $sum + $cryptoPrice->convertToUSD($wallet->balance, $wallet->currency);
        }, 0);

        $pendingPayments = PaymentRequest::where('merchant_id', auth()->id())
            ->where('status', 'pending')
            ->count();
        $completedPayments = PaymentRequest::where('merchant_id', auth()->id())
            ->where('status', 'paid')
            ->count();
        $payments = PaymentRequest::where('merchant_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();

        // Transaction Statistics
        $merchantTransactionScope = function ($query) {
            $query->whereHas('wallet', function ($q) {
                $q->where('user_id', auth()->id());
            })->orWhere('merchant_id', auth()->id());
        };
 
        $totalTransactions = Transaction::where($merchantTransactionScope)->count();
 
        $successfulTransactions = Transaction::where($merchantTransactionScope)
            ->whereIn('status', ['completed', 'confirmed'])->count();
 
        $failedTransactions = Transaction::where($merchantTransactionScope)
            ->whereIn('status', ['pending', 'failed', 'cancelled', 'rejected'])->count();

        // درآمد روزانه برای نمودار (۷ روز گذشته)
        $dailyRevenue = [];
        $dailyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->locale(app()->getLocale());
            $dailyLabels[] = $date->translatedFormat('l');
            
            $dailyPayments = PaymentRequest::where('merchant_id', auth()->id())
                ->where('status', 'paid')
                ->whereDate('updated_at', $date)
                ->get();

            $dayRevenue = $dailyPayments->reduce(function ($sum, $payment) use ($cryptoPrice) {
                return $sum + $cryptoPrice->convertToUSD($payment->amount, $payment->currency);
            }, 0);
            
            $dailyRevenue[] = (float)$dayRevenue;
        }

        return view('merchant.dashboard-new', compact(
            'wallets', 'totalRevenue', 'pendingPayments', 'completedPayments', 'payments',
            'totalTransactions', 'successfulTransactions', 'failedTransactions',
            'dailyRevenue', 'dailyLabels'
        ));
    }

    public function wallets()
    {
        $wallets = auth()->user()->wallets ?? collect();
        return view('merchant.wallets', compact('wallets'));
    }

    public function transactions()
    {
        $merchantTransactionScope = function ($query) {
            $query->whereHas('wallet', function ($q) {
                $q->where('user_id', auth()->id());
            })->orWhere('merchant_id', auth()->id());
        };

        $query = Transaction::where($merchantTransactionScope)
            ->with(['wallet', 'sender', 'recipient', 'paymentRequest', 'deposit']);

        // Search by transaction ID or description
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('id', 'like', "%$search%")
                  ->orWhereHas('sender', function($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  });
            });
        }

        // Filter by type
        if (request('type') && request('type') !== '') {
            $query->where('type', request('type'));
        }

        // Filter by status
        if (request('status') && request('status') !== '') {
            $query->where('status', request('status'));
        }

        // Get all transactions combined with payment requests and wallet activities
        $transactions = $query->latest()->paginate(20);
        
        // Get payment requests for this merchant
        $paymentRequestsQuery = PaymentRequest::where('merchant_id', auth()->id())
            ->with('recipient');
        
        if (request('search')) {
            $search = request('search');
            $paymentRequestsQuery->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%")
                  ->orWhereHas('recipient', function($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  });
            });
        }

        if (request('status') && request('status') !== '') {
            $paymentRequestsQuery->where('status', request('status'));
        }

        $paymentRequests = $paymentRequestsQuery->latest()->get();

        // Calculate stats from ALL transactions and payments (not just paginated/filtered)
        $allTransactions = Transaction::where(function ($query) {
            $query->whereHas('wallet', function ($q) {
                $q->where('user_id', auth()->id());
            })->orWhere('merchant_id', auth()->id());
        })->get();
        
        $allPaymentRequests = PaymentRequest::where('merchant_id', auth()->id())->get();
        
        $totalCount = $allTransactions->count() + $allPaymentRequests->count();
        $completedCount = $allTransactions->whereIn('status', ['completed', 'confirmed'])->count() + $allPaymentRequests->where('status', 'paid')->count();
        $pendingCount = $allTransactions->where('status', 'pending')->count() + $allPaymentRequests->where('status', 'pending')->count();
        
        // Failed count includes: failed transactions, rejected and cancelled payments
        $failedCount = $allTransactions->where('status', 'failed')->count() + 
                       $allTransactions->where('status', 'rejected')->count() +
                       $allPaymentRequests->where('status', 'rejected')->count() + 
                       $allPaymentRequests->where('status', 'cancelled')->count();

        // Get wallet activities (deposits/withdrawals)
        $walletTransactions = $transactions;
        
        $availableCurrencies = Wallet::where('user_id', auth()->id())->pluck('currency')->unique()->values();
        
        return view('merchant.transactions', compact('transactions', 'paymentRequests', 'walletTransactions', 'totalCount', 'completedCount', 'pendingCount', 'failedCount', 'availableCurrencies'));
    }

    /**
     * Export transactions and payment requests matching current filters as CSV
     */
    public function exportTransactions()
    {
        $query = Transaction::where(function ($query) {
            $query->whereHas('wallet', function ($q) {
                $q->where('user_id', auth()->id());
            })->orWhere('merchant_id', auth()->id());
        })->with(['wallet', 'sender', 'recipient', 'paymentRequest']);

        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('id', 'like', "%$search%")
                  ->orWhereHas('sender', function($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  });
            });
        }

        if (request('type') && request('type') !== '') {
            $query->where('type', request('type'));
        }

        if (request('status') && request('status') !== '') {
            $query->where('status', request('status'));
        }

        $transactions = $query->latest()->get();

        // Also include payment requests (invoices) optionally filtered
        $paymentRequestsQuery = PaymentRequest::where('merchant_id', auth()->id())->with('recipient');
        if (request('search')) {
            $search = request('search');
            $paymentRequestsQuery->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%")
                  ->orWhereHas('recipient', function($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  });
            });
        }
        if (request('status') && request('status') !== '') {
            $paymentRequestsQuery->where('status', request('status'));
        }
        $paymentRequests = $paymentRequestsQuery->latest()->get();

        $filename = 'merchant-transactions-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($transactions, $paymentRequests) {
            $out = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility with Unicode/Persian text
            fwrite($out, "\xEF\xBB\xBF");
            // Header row
            fputcsv($out, ['type','id','reference','invoice_number','description','amount','currency','status','sender','recipient','created_at']);

            foreach ($transactions as $t) {
                $senderName = $t->sender?->name ?? $t->sender_wallet_address ?? '';
                $recipientName = $t->recipient?->name ?? '';

                fputcsv($out, [
                    'transaction',
                    $t->id,
                    $t->reference ?? '',
                    $t->paymentRequest?->invoice_number ?? '',
                    $t->description ?? '',
                    $t->amount,
                    $t->currency ?? ($t->wallet?->currency ?? ''),
                    $t->status,
                    $senderName,
                    $recipientName,
                    $t->created_at->toDateTimeString(),
                ]);
            }

            foreach ($paymentRequests as $p) {
                fputcsv($out, [
                    'invoice',
                    $p->id,
                    $p->reference ?? '',
                    $p->invoice_number ?? '',
                    'Invoice: ' . ($p->description ?? ''),
                    $p->amount,
                    $p->currency,
                    $p->status,
                    '',
                    $p->recipient?->name ?? '',
                    $p->created_at->toDateTimeString(),
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download a single transaction summary (text/html).
     */
    public function downloadTransaction(Transaction $transaction)
    {
        // Ensure merchant owns the wallet related to transaction
        if ($transaction->wallet->user_id !== auth()->id()) {
            abort(403);
        }

        $data = [
            'id' => $transaction->id,
            'reference' => $transaction->reference,
            'type' => $transaction->type,
            'description' => $transaction->description,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency ?? $transaction->wallet->currency ?? '',
            'status' => $transaction->status,
            'sender' => $transaction->sender?->name ?? '—',
            'recipient' => $transaction->recipient?->name ?? '—',
            'created_at' => $transaction->created_at->toDateTimeString(),
        ];

        $filename = 'transaction-' . ($transaction->reference ?? $transaction->id) . '.html';

        $content = view('merchant.downloads.transaction', compact('data'))->render();

        return response($content, 200)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    /**
     * Download invoice (payment request) as simple HTML invoice file
     */
    public function downloadInvoice(PaymentRequest $invoice)
    {
        if ($invoice->merchant_id !== auth()->id()) {
            abort(403);
        }

        $data = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'reference' => $invoice->reference,
            'description' => $invoice->description,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
            'recipient' => $invoice->recipient?->name ?? '—',
            'created_at' => $invoice->created_at->toDateTimeString(),
            'paid_at' => $invoice->updated_at && $invoice->status === 'paid' ? $invoice->updated_at->toDateTimeString() : null,
        ];

        $filename = 'invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.html';

        $content = view('merchant.downloads.invoice', compact('data'))->render();

        return response($content, 200)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    public function invoices()
    {
        $invoices = PaymentRequest::where('merchant_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);
        
        return view('merchant.invoices', compact('invoices'));
    }

    public function customers()
    {
        $customers = auth()->user()->customers()
            ->with(['user'])
            ->withCount('paymentRequests')
            ->latest()
            ->paginate(20);

        return view('merchant.customers', compact('customers'));
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::where('name', $request->username)
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return back()->withErrors(['username' => 'نام کاربری یا ایمیل صحیح نیست. ابتدا باید یک کاربر با این نام کاربری و ایمیل در سیستم وجود داشته باشد.']);
        }

        if (auth()->user()->customers()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['username' => 'این مشتری قبلاً ثبت شده است.']);
        }

        auth()->user()->customers()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $request->phone,
        ]);

        return redirect()->route('merchant.customers')->with('success', 'مشتری جدید با موفقیت اضافه شد');
    }

    public function editCustomer(Customer $customer)
    {
        if ($customer->merchant_id !== auth()->id()) {
            abort(403);
        }

        return view('merchant.customer-edit', compact('customer'));
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        if ($customer->merchant_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::where('name', $request->username)
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return back()->withErrors(['username' => 'نام کاربری یا ایمیل صحیح نیست.']);
        }

        if (auth()->user()->customers()->where('user_id', $user->id)->where('id', '!=', $customer->id)->exists()) {
            return back()->withErrors(['username' => 'این مشتری قبلاً ثبت شده است.']);
        }

        $customer->update([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $request->phone,
        ]);

        return redirect()->route('merchant.customers')->with('success', 'مشتری با موفقیت بروزرسانی شد');
    }

    public function destroyCustomer(Customer $customer)
    {
        if ($customer->merchant_id !== auth()->id()) {
            abort(403);
        }

        $customer->delete();

        return redirect()->route('merchant.customers')->with('success', 'مشتری با موفقیت حذف شد');
    }

    public function showCustomer(Customer $customer)
    {
        if ($customer->merchant_id !== auth()->id()) {
            abort(403);
        }

        $invoices = $customer->paymentRequests()->latest()->get();

        return view('merchant.customer-cardex', compact('customer', 'invoices'));
    }

    public function settlements()
    {
        $settlements = PaymentRequest::where('merchant_id', auth()->id())
            ->where('status', 'paid')
            ->latest()
            ->paginate(20);
        
        return view('merchant.settlements', compact('settlements'));
    }

    public function settings()
    {
        return view('merchant.settings');
    }

    public function updateMerchantSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'shop_name' => 'required|string|max:255',
            'shop_description' => 'nullable|string|max:1000',
            'business_email' => 'nullable|email|max:255',
            'business_phone' => 'nullable|string|max:20',
            'business_address' => 'nullable|string|max:500',
            'website_url' => 'nullable|url|max:255',
            'business_license' => 'nullable|string|max:255',
            // avatar
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = auth()->user();
        $user->update($validated);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
            $user->save();
        }

        return redirect()->route('merchant.settings')->with('success', __('merchant_settings.update_success'));
    }

    public function apikeys()
    {
        // TODO: Implement API keys view
        return view('merchant.apikeys');
    }

    public function storeWallet(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|in:BTC,ETH,USDT',
        ]);

        // Create a wallet record and let the Wallet model's creating() hook
        // generate a real HD wallet (address + encrypted private key) via
        // BlockchainWalletService. Do not use ad-hoc random generation here.
        try {
            DB::transaction(function () use ($validated) {
                auth()->user()->wallets()->create([
                    'currency' => $validated['currency'],
                    'balance' => 0,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create merchant wallet', [
                'merchant_id' => auth()->id(),
                'currency' => $validated['currency'],
                'error_message' => $e->getMessage(),
            ]);
            return redirect()->route('merchant.wallets')->withErrors(['currency' => 'Unable to create wallet at this time. Please try again later.']);
        }

        return redirect()->route('merchant.wallets')->with('success', __('wallets.create_wallet_success', ['currency' => $request->currency]));
    }

    public function rename(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
        ]);

        $wallet->update([
            'name' => trim((string) ($validated['name'] ?? '')) !== '' ? trim((string) $validated['name']) : null,
        ]);

        return redirect()->route('merchant.wallets')->with('success', __('wallets.rename_wallet_success'));
    }

    /**
     * Delete a merchant wallet. Only owner (merchant) can delete and balance must be zero.
     */
    public function destroyWallet(Wallet $wallet)
    {
        if ($wallet->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (floatval($wallet->balance) > 0) {
            return redirect()->route('merchant.wallets')->withErrors(['wallet' => __('wallets.delete_wallet_balance_alert')]);
        }

        try {
            $wallet->delete();
        } catch (\Throwable $e) {
            return redirect()->route('merchant.wallets')->withErrors(['wallet' => __('wallets.delete_wallet_error')]);
        }

        return redirect()->route('merchant.wallets')->with('success', __('wallets.delete_wallet_success'));
    }

    private function generateWalletAddress($currency)
    {
        // Use BlockchainWalletService to derive a proper address if needed.
        $service = new \App\Services\BlockchainWalletService();
        try {
            $res = $service->generateHdWallet($currency);
            return $res['address'] ?? null;
        } catch (\Throwable $e) {
            \Log::error('Failed to generate wallet address: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendCrypto(Request $request)
    {
        $request->validate([
            'sender_wallet_id' => 'required|integer|exists:wallets,id',
            'wallet_address' => 'required|string',
            'amount' => 'required|string',
        ]);

        $network = env('ETHEREUM_NETWORK', 'sepolia');
        if (strtolower((string)$network) !== 'sepolia') {
            return back()->withErrors(['wallet_address' => 'Ethereum network is not configured for Sepolia.'])->withInput();
        }

        $wallet = Wallet::where('id', $request->sender_wallet_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (strtoupper((string)$wallet->currency) !== 'ETH') {
            return back()->withErrors(['sender_wallet_id' => 'Only ETH wallets can be used for Sepolia outgoing transfers.'])->withInput();
        }

        if (empty($wallet->wallet_address)) {
            return back()->withErrors(['sender_wallet_id' => 'Sender wallet address is missing.'])->withInput();
        }

        $destination = trim($request->wallet_address);
        $ethService = new EthereumService();
        if (!$ethService->isValidAddress($destination)) {
            return back()->withErrors(['wallet_address' => 'Invalid Ethereum destination address.'])->withInput();
        }

        if (strtolower($wallet->wallet_address) === strtolower($destination)) {
            return back()->withErrors(['wallet_address' => 'Destination address cannot be the sender wallet.'])->withInput();
        }

        $amount = trim((string)$request->amount);
        if (!preg_match('/^\d+(\.\d+)?$/', $amount) || bccomp($amount, '0', 18) <= 0) {
            return back()->withErrors(['amount' => 'The amount must be a positive ETH string value greater than zero.'])->withInput();
        }

        try {
            $amountWei = $ethService->parseEther($amount);
            if ((string)$amountWei === '0' || (int)$amountWei <= 0) {
                return back()->withErrors(['amount' => 'The amount must be greater than zero.'])->withInput();
            }
        } catch (\Throwable $e) {
            Log::error('ParseEther failure: ' . $e->getMessage());
            return back()->withErrors(['amount' => 'Invalid ETH amount.'])->withInput();
        }

        // Create transaction record with 'processing' status and dispatch background job.
        // Do not perform any estimate, signing, or RPC broadcast inside the HTTP request.

        // Ensure user wallet has sufficient balance for the requested amount (gas will be handled in the job)
        $walletBalance = (string)$wallet->balance;
        if (bccomp($walletBalance, $amount, 18) < 0) {
            return back()->withErrors(['amount' => 'Insufficient ETH balance for requested amount.'])->withInput();
        }

        // Persist a 'processing' transaction entry immediately so job can pick it up.
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => null,
            'merchant_id' => auth()->id(),
            'sender_id' => auth()->id(),
            'recipient_id' => null,
            'type' => 'withdraw',
            'amount' => $amount,
            'currency' => 'ETH',
            'status' => 'processing',
            'reference' => null,
            'description' => 'ETH Sepolia send',
            'sender_wallet_address' => $wallet->wallet_address,
            'receiver_wallet_address' => $destination,
            'tx_hash' => null,
        ]);

        try {
            \App\Jobs\SendCryptoTransaction::dispatch($transaction->id)->onQueue(config('queue.connections.database.queue', 'default'));
        } catch (\Throwable $e) {
            // If dispatch fails, mark transaction as failed and inform user
            Log::error('Failed to dispatch SendCryptoTransaction job', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            $transaction->status = 'failed';
            $transaction->save();
            return back()->withErrors(['wallet_address' => 'Unable to queue transaction for processing. Please try again later.'])->withInput();
        }

        // Warn if queue driver is 'sync' since that would run the job synchronously and defeat the async goal
        if (config('queue.default') === 'sync') {
            Log::warning('Queue connection is configured as sync; background job will run inline. Set QUEUE_CONNECTION=database and run a queue worker for true background processing.');
        }

        // Keep the user on the transactions list after creating the transaction instead
        // of redirecting to the transaction detail page.
        return redirect()
            ->route('merchant.transactions')
            ->with('success', 'Transaction has been queued and is being processed.');
    }

    public function showTransaction(Transaction $transaction)
    {
        // Ensure merchant owns this transaction
        if ($transaction->merchant_id !== auth()->id()) {
            abort(403);
        }

        return view('transactions.show', compact('transaction'));
    }

    public function send(Request $request)
    {
        // Allow optional pre-selected sender wallet via query param
        $wallets = auth()->user()->wallets ?? collect();
        $preselected = $request->query('sender_wallet_id');
        return view('merchant.send', compact('wallets', 'preselected'));
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'wallet_address'   => 'required',
            'sender_wallet_id' => 'required|integer',
            'amount'           => 'required|numeric|min:0.00000001'
        ]);

        $senderWallet = Wallet::where(
            'id',
            $request->sender_wallet_id
        )->where(
            'user_id',
            auth()->id()
        )->firstOrFail();

        $receiverWallet = Wallet::where(
            'wallet_address',
            $request->wallet_address
        )->first();

        if (!$receiverWallet) {
            return back()->withErrors([
                'wallet_address' => 'Wallet not found'
            ])->withInput();
        }

        if ($senderWallet->id === $receiverWallet->id) {
            return back()->withErrors([
                'wallet_address' => 'Cannot transfer to the same wallet'
            ])->withInput();
        }

        if (
            $senderWallet->currency !==
            $receiverWallet->currency
        ) {
            return back()->withErrors([
                'wallet_address' => 'Wallet currency mismatch'
            ])->withInput();
        }

        $two = \App\Models\TwoFactor::where('user_id', auth()->id())->first();
        if (!$two || empty($two->secret_enc)) {
            return back()->withErrors([
                'two_factor_token' => 'برای ارسال ارز دیجیتال، احراز هویت دو مرحله‌ای باید فعال باشد.'
            ])->withInput();
        }

        $twoFactorToken = $request->input('two_factor_token');
        if (!$twoFactorToken) {
            return back()->withErrors(['two_factor_token' => 'کد احراز هویت دو مرحله‌ای لازم است'])->withInput();
        }

        try {
            $secret = Crypt::decryptString($two->secret_enc);
        } catch (\Throwable $e) {
            return back()->withErrors(['two_factor_token' => 'خطای پیکربندی 2FA'])->withInput();
        }

        if (!\App\Services\TOTP::verifyCode($secret, $twoFactorToken, 1)) {
            try {
                DB::table('audit_logs')->insert([
                    'actor_id' => auth()->id(),
                    'user_id' => auth()->id(),
                    'action' => '2fa_failed_transfer',
                    'resource_type' => 'wallet',
                    'resource_id' => $senderWallet->id,
                    'diff' => json_encode(['ip' => $request->ip()]),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {}

            return back()->withErrors(['two_factor_token' => 'کد ۲FA نامعتبر است'])->withInput();
        }

        if ($senderWallet->balance < $request->amount) {
            return back()->withErrors([
                'amount' => 'Insufficient balance'
            ])->withInput();
        }

        DB::transaction(function () use (
            $senderWallet,
            $receiverWallet,
            $request
        ) {

            $senderWallet->decrement(
                'balance',
                $request->amount
            );

            $receiverWallet->increment(
                'balance',
                $request->amount
            );

            // Create transaction for sender
            Transaction::create([
                'wallet_id'   => $senderWallet->id,
                'sender_id'   => auth()->id(),
                'recipient_id' => $receiverWallet->user_id,
                'type'        => 'transfer',
                'amount'      => $request->amount,
                'status'      => 'completed',
                'reference'   => 'TRX-' . time(),
                'description' => $request->description ?? 'Transfer Sent'
            ]);

            // Create transaction for recipient
            Transaction::create([
                'wallet_id'   => $receiverWallet->id,
                'sender_id'   => auth()->id(),
                'recipient_id' => $receiverWallet->user_id,
                'type'        => 'deposit',
                'amount'      => $request->amount,
                'status'      => 'completed',
                'reference'   => 'TRX-' . time(),
                'description' => 'Transfer Received'
            ]);

            // Create notifications
            Notification::createNotification(
                auth()->id(),
                __('notifications.transfer_sent.title'),
                __('notifications.transfer_sent.message', [
                    'amount' => number_format($request->amount, 8),
                    'currency' => $senderWallet->currency,
                    'name' => $receiverWallet->user->name,
                ]),
                'success',
                'fa-paper-plane'
            );

            Notification::createNotification(
                $receiverWallet->user_id,
                __('notifications.transfer_received.title'),
                __('notifications.transfer_received.message', [
                    'amount' => number_format($request->amount, 8),
                    'currency' => $receiverWallet->currency,
                    'name' => auth()->user()->name,
                ]),
                'success',
                'fa-inbox'
            );
        });

        return back()->with(
            'success',
            'Transfer completed successfully'
        );
    }

    public function index()
    {
        $wallets = auth()->user()->wallets ?? collect();
        return view('merchant.dashboard-new', compact('wallets'));
    }
}
