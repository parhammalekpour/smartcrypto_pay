<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\PaymentRequest;
use App\Models\Notification;
use App\Services\CryptoPrice;
use App\Services\EthereumService;

class WalletController extends Controller
{
    public function dashboard(Request $request)
    {
        $wallets = auth()->user()->wallets ?? collect();
        
        // Calculate total balance in USD using real-time prices
        $cryptoPrice = new CryptoPrice();
        $totalBalance = 0;
        
        foreach ($wallets as $wallet) {
            // Convert any currency to USD
            $usdValue = $cryptoPrice->convertToUSD($wallet->balance, $wallet->currency);
            $totalBalance += $usdValue;
        }
        
        $transactions = Transaction::whereIn(
            'wallet_id',
            $wallets->pluck('id')
        )->with(['wallet', 'deposit'])->latest()->take(10)->get() ?? collect();

        $receivedCount = Transaction::whereIn('wallet_id', $wallets->pluck('id'))
            ->where('type', 'deposit')
            ->whereMonth('created_at', now()->month)
            ->count();

        $sentCount = Transaction::whereIn('wallet_id', $wallets->pluck('id'))
            ->where('type', 'transfer')
            ->whereMonth('created_at', now()->month)
            ->count();

        $pendingPayments = PaymentRequest::where('recipient_user_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('user.dashboard-new', compact(
            'wallets',
            'totalBalance',
            'transactions',
            'receivedCount',
            'sentCount',
            'pendingPayments'
        ));
    }

    public function wallets()
    {
        $wallets = auth()->user()->wallets ?? collect();

        // Quick sync balances for the current user's wallets to ensure UI shows up-to-date values
        try {
            $balanceService = new \App\Services\BalanceSyncService();
            foreach ($wallets as $wallet) {
                try {
                    $balanceService->syncWallet($wallet);
                } catch (\Throwable $e) {
                    Log::error('Quick sync failed for wallet ' . ($wallet->id ?? '?') . ': ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            // Guard - do not block page rendering on sync errors
            Log::warning('Balance quick-sync initialization failed: ' . $e->getMessage());
        }

        return view('user.wallets', compact('wallets'));
    }

    public function storeWallet(Request $request)
    {
        $request->validate([
            'currency' => 'required|in:BTC,ETH,USDT'
        ]);

        // Create a wallet record and let the Wallet model's creating() hook
        // generate a real HD wallet (address + encrypted private key) via
        // BlockchainWalletService. This avoids producing invalid/fake addresses.
        try {
            DB::transaction(function () use ($request) {
                Wallet::create([
                    'user_id' => auth()->id(),
                    'currency' => $request->currency,
                    'balance' => 0,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create wallet for user', [
                'user_id' => auth()->id(),
                'currency' => $request->currency,
                'error_message' => $e->getMessage(),
            ]);

            return back()->withErrors(['currency' => 'Unable to create wallet at this time. Please try again later.'])->withInput();
        }

        return back()->with('success', __('wallets.create_wallet_success', ['currency' => $request->currency]));
    }

    /**
     * Delete a user's wallet. Only owner can delete and only when balance is zero.
     */
    public function destroy(Wallet $wallet)
    {
        // Ensure the authenticated user owns this wallet
        if ($wallet->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Prevent deletion when balance is non-zero
        if (floatval($wallet->balance) > 0) {
            return back()->withErrors(['wallet' => __('wallets.delete_wallet_balance_alert')]);
        }

        try {
            $wallet->delete();
        } catch (\Throwable $e) {
            return back()->withErrors(['wallet' => __('wallets.delete_wallet_error')]);
        }

        return back()->with('success', __('wallets.delete_wallet_success'));
    }

    private function generateWalletAddress($currency)
    {
        // Prefer generating via BlockchainWalletService (ethers.js) so addresses
        // are always valid and properly formatted. This helper is retained for
        // backward compatibility but uses the same service used elsewhere.
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

        try {
            $balanceService = new \App\Services\BalanceSyncService();
            $balanceService->syncWallet($wallet);
            $wallet->refresh();
        } catch (\Throwable $e) {
            Log::warning('Balance sync before sendCrypto failed for wallet ' . $wallet->id . ': ' . $e->getMessage());
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

        // Validate parseEther via ethers.js and keep raw string usage instead of float.
        try {
            $amountWei = $ethService->parseEther($amount);
            if ((string)$amountWei === '0' || (int)$amountWei <= 0) {
                return back()->withErrors(['amount' => 'The amount must be greater than zero.'])->withInput();
            }
        } catch (\Throwable $e) {
            Log::error('ParseEther failure: ' . $e->getMessage());
            return back()->withErrors(['amount' => 'Invalid ETH amount.'])->withInput();
        }

        $privateKey = $wallet->getPrivateKey();
        if (empty($privateKey)) {
            Log::error('Attempted ETH send without decryptable private key for wallet ' . $wallet->id, [
                'wallet_id' => $wallet->id,
                'wallet_address' => $wallet->wallet_address,
            ]);
            return back()->withErrors(['sender_wallet_id' => 'This wallet cannot sign transactions because its private key is unavailable.'])->withInput();
        }

        // Create transaction record with 'processing' status and dispatch background job.
        // Do not perform estimate, signing, or RPC broadcast inside the HTTP request.

        // Ensure user wallet has sufficient balance for the requested amount (gas will be handled in the job)
        $walletBalance = (string)$wallet->balance;
        if (bccomp($walletBalance, $amount, 18) < 0) {
            return back()->withErrors(['amount' => 'Insufficient ETH balance for requested amount.'])->withInput();
        }

        // Persist a 'processing' transaction entry immediately so job can pick it up.
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => auth()->id(),
            'merchant_id' => null,
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
            Log::error('Failed to dispatch SendCryptoTransaction job', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            $transaction->status = 'failed';
            $transaction->save();
            return back()->withErrors(['wallet_address' => 'Unable to queue transaction for processing. Please try again later.'])->withInput();
        }

        if (config('queue.default') === 'sync') {
            Log::warning('Queue connection is configured as sync; background job will run inline. Set QUEUE_CONNECTION=database and run a queue worker for true background processing.');
        }

        // Redirect to the transactions list so the user stays on the transactions page.
        return redirect()
            ->route('user.transactions')
            ->with('success', 'Transaction has been queued and is being processed.');
    }

    public function showTransaction(Transaction $transaction)
    {
        // Ensure the authenticated user has access via the wallet
        if (!$transaction->wallet || $transaction->wallet->user_id !== auth()->id()) {
            abort(403);
        }

        return view('transactions.show', compact('transaction'));
    }

    public function send(Request $request)
    {
        $wallets = auth()->user()->wallets ?? collect();
        $preselected = $request->query('sender_wallet_id');
        return view('user.send', compact('wallets', 'preselected'));
    }

    public function receive()
    {
        $wallets = auth()->user()->wallets ?? collect();
        return view('user.receive', compact('wallets'));
    }

    public function transactions(Request $request)
    {
        $wallets = auth()->user()->wallets ?? collect();
        $walletIds = $wallets->pluck('id')->filter()->all();
        
        $query = Transaction::whereIn('wallet_id', $walletIds);

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by currency
        if ($request->currency) {
            $query->whereHas('wallet', function ($q) use ($request) {
                $q->where('currency', $request->currency);
            });
        }

        // Filter by amount range
        if ($request->amount_range) {
            $range = explode('-', $request->amount_range);
            if (count($range) === 2) {
                $min = (float) $range[0];
                $max = $range[1] === '+' ? PHP_INT_MAX : (float) $range[1];
                $query->whereBetween('amount', [$min, $max]);
            }
        }

        // Filter by search (name or reference)
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhereHas('sender', function ($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('recipient', function ($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $transactions = $query->with(['paymentRequest.merchant', 'sender', 'recipient', 'wallet', 'deposit'])->latest()->paginate(20);

        $stats = [
            'total' => Transaction::whereIn('wallet_id', $walletIds)->count(),
            'completed' => Transaction::whereIn('wallet_id', $walletIds)->whereIn('status', ['completed', 'confirmed'])->count(),
            'pending' => Transaction::whereIn('wallet_id', $walletIds)->where('status', 'pending')->count(),
            'failed' => Transaction::whereIn('wallet_id', $walletIds)->where('status', 'failed')->count(),
        ];
        
        return view('user.transactions', compact('transactions', 'wallets', 'stats'));
    }

    public function pendingPayments()
    {
        $payments = PaymentRequest::where('recipient_user_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);
        return view('user.pending-payments', compact('payments'));
    }

    public function settings()
    {
        return view('user.settings');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'show_balance' => 'nullable|boolean',
            'show_transactions' => 'nullable|boolean',
            'dark_mode' => 'nullable|boolean',
            'notifications_enabled' => 'nullable|boolean',
            'notifications_email' => 'nullable|email',
            'notifications_2fa' => 'nullable|boolean',
        ]);

        // Convert checkboxes to boolean (unchecked = 0, checked = 1)
        $updateData = [];
        foreach (['show_balance', 'show_transactions', 'dark_mode', 'notifications_enabled', 'notifications_2fa'] as $field) {
            $updateData[$field] = $request->has($field) ? 1 : 0;
        }

        // Merge with validated data
        $updateData = array_merge($validated, $updateData);

        // Handle avatar upload if present
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $path;
        }

        auth()->user()->update($updateData);

        return redirect()->route('user.settings')->with('success', __('wallets.settings_updated_success'));
    }

    public function logoutAllDevices(Request $request)
    {
        // Delete all tokens for this user
        auth()->user()->tokens()->delete();
        
        // Logout current session
        auth()->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', __('wallets.logged_out_all_devices_success'));
    }

    public function index(Request $request)
    {
        $wallets = auth()->user()->wallets ?? collect();

        $query = Transaction::whereIn(
            'wallet_id',
            $wallets->pluck('id')
        );

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->search) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->latest()->get();

        return view('user', compact(
            'wallets',
            'transactions'
        ));
    }

    public function demoDeposit(Wallet $wallet)
    {
        if ($wallet->user_id !== auth()->id()) {
            abort(403);
        }

        switch ($wallet->currency) {
            case 'BTC':
                $amount = 0.01;
                break;
            case 'ETH':
                $amount = 1;
                break;
            default:
                $amount = 1000;
                break;
        }

        $wallet->increment('balance', $amount);

        Transaction::create([
            'wallet_id'   => $wallet->id,
            'sender_id'   => null,
            'recipient_id' => auth()->id(),
            'type'        => 'deposit',
            'amount'      => $amount,
            'status'      => 'completed',
            'reference'   => 'DEMO-' . time(),
            'description' => 'Demo Deposit'
        ]);

        Notification::createNotification(
            auth()->id(),
            __('notifications.demo_deposit.title'),
            __('notifications.demo_deposit.message', [
                'amount' => number_format($amount, 8),
                'currency' => $wallet->currency,
            ]),
            'success',
            'fa-gift'
        );

        return back()->with(
            'success',
            $amount . ' ' . $wallet->currency . ' added successfully'
        );
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
            ]);
        }

        if ($senderWallet->id === $receiverWallet->id) {
            return back()->withErrors([
                'wallet_address' => 'Cannot transfer to the same wallet'
            ]);
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
            ]);
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
                'description' => 'Transfer Sent'
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

    public function showTransfer()
    {
        $wallets = auth()->user()->wallets;
        return view('transfer', compact('wallets'));
    }

    public function showPayments()
    {
        $payments = PaymentRequest::where('recipient_user_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->get();
        return view('user-payments', compact('payments'));
    }

    public function getPrices()
    {
        // Prefer a background-fetched file for maximum reliability if present
        $path = storage_path('app/crypto_prices.json');
        if (file_exists($path)) {
            $content = json_decode(file_get_contents($path), true);
            if (
                is_array($content)
                && isset($content['btc'], $content['eth'])
                && is_numeric($content['btc'])
                && is_numeric($content['eth'])
                && (float) $content['btc'] > 0
                && (float) $content['eth'] > 0
            ) {
                return response()->json($content)
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }

            Log::warning('Ignored invalid or stale crypto_prices.json cache; falling back to live Binance quote.');
        }

        // Fallback: query live service (with caching inside service)
        $cryptoPrice = new CryptoPrice();

        $resp = response()->json([
            'btc' => $cryptoPrice->getPrice('BTC'),
            'eth' => $cryptoPrice->getPrice('ETH'),
            'usd' => 1,
            'timestamp' => now()->getTimestamp()
        ]);

        return $resp->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
    }

    public function rejectPayment($id)
    {
        $payment = PaymentRequest::findOrFail($id);

        // Verify user is the recipient
        if ($payment->recipient_user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Update status to rejected
        $payment->update(['status' => 'rejected']);

        // Create transaction for rejected payment (for merchant dashboard stats)
        if ($payment->merchant_id) {
            $merchant = \App\Models\User::find($payment->merchant_id);
            if ($merchant) {
                // Get merchant's wallet for this currency
                $wallet = $merchant->wallets()->where('currency', $payment->currency)->first();
                
                if ($wallet) {
                    Transaction::create([
                        'wallet_id' => $wallet->id,
                        'sender_id' => auth()->id(),
                        'recipient_id' => $payment->merchant_id,
                        'type' => 'payment',
                        'amount' => $payment->amount,
                        'status' => 'rejected',
                        'reference' => $payment->invoice_number,
                        'description' => 'درخواست پرداخت رد شد'
                    ]);
                }
            }
        }

        // Create notification for merchant
        Notification::createNotification(
            $payment->merchant_id,
            __('notifications.payment_rejected.title'),
            __('notifications.payment_rejected.message', [
                'invoice' => $payment->invoice_number,
                'name' => auth()->user()->name,
            ]),
            'danger',
            'fa-times-circle'
        );

        return back()->with('success', __('wallets.payment_request_rejected_success'));
    }
}
