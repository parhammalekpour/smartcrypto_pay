<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\PaymentRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Notification;

class MerchantController extends Controller
{
    public function dashboard()
    {
        $wallets = auth()->user()->wallets ?? collect();
        // فقط موجودی USDT را محاسبه کنید
        $totalRevenue = $wallets->where('currency', 'USDT')->sum('balance') ?? 0;
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
        $totalTransactions = Transaction::whereHas('wallet', function ($query) {
            $query->where('user_id', auth()->id());
        })->count();

        $successfulTransactions = Transaction::whereHas('wallet', function ($query) {
            $query->where('user_id', auth()->id());
        })->where('status', 'completed')->count();

        $failedTransactions = Transaction::whereHas('wallet', function ($query) {
            $query->where('user_id', auth()->id());
        })->whereIn('status', ['pending', 'failed', 'cancelled', 'rejected'])->count();

        // درآمد روزانه برای نمودار (۷ روز گذشته)
        $dailyRevenue = [];
        $dailyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayName = ['یکش', 'دوش', 'سهش', 'چهارش', 'پنجش', 'جمعه', 'شنبه'][
                $date->dayOfWeek == 0 ? 6 : $date->dayOfWeek - 1
            ];
            $dailyLabels[] = $dayName;
            
            $revenue = PaymentRequest::where('merchant_id', auth()->id())
                ->where('status', 'paid')
                ->whereDate('updated_at', $date)
                ->sum('amount');
            
            $dailyRevenue[] = (float)($revenue ?? 0);
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
        $query = Transaction::whereHas('wallet', function ($query) {
            $query->where('user_id', auth()->id());
        })->with(['wallet', 'sender', 'recipient', 'paymentRequest']);

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
        $allTransactions = Transaction::whereHas('wallet', function ($q) {
            $q->where('user_id', auth()->id());
        })->get();
        
        $allPaymentRequests = PaymentRequest::where('merchant_id', auth()->id())->get();
        
        $totalCount = $allTransactions->count() + $allPaymentRequests->count();
        $completedCount = $allTransactions->where('status', 'completed')->count() + $allPaymentRequests->where('status', 'paid')->count();
        $pendingCount = $allTransactions->where('status', 'pending')->count() + $allPaymentRequests->where('status', 'pending')->count();
        
        // Failed count includes: failed transactions, rejected and cancelled payments
        $failedCount = $allTransactions->where('status', 'failed')->count() + 
                       $allTransactions->where('status', 'rejected')->count() +
                       $allPaymentRequests->where('status', 'rejected')->count() + 
                       $allPaymentRequests->where('status', 'cancelled')->count();

        // Get wallet activities (deposits/withdrawals)
        $walletTransactions = $transactions;
        
        return view('merchant.transactions', compact('transactions', 'paymentRequests', 'walletTransactions', 'totalCount', 'completedCount', 'pendingCount', 'failedCount'));
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
        ]);

        auth()->user()->update($validated);

        return redirect()->route('merchant.settings')->with('success', 'تنظیمات فروشنده بروزرسانی شد');
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

        // Generate a public wallet address based on currency
        $walletAddress = $this->generateWalletAddress($validated['currency']);

        auth()->user()->wallets()->create([
            'currency' => $validated['currency'],
            'wallet_address' => $walletAddress,
            'balance' => 0,
        ]);

        return redirect()->route('merchant.wallets')->with('success', 'کیف پول با موفقیت اضافه شد');
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
            return redirect()->route('merchant.wallets')->withErrors(['wallet' => 'برای حذف کیف پول، موجودی باید صفر باشد.']);
        }

        try {
            $wallet->delete();
        } catch (\Throwable $e) {
            return redirect()->route('merchant.wallets')->withErrors(['wallet' => 'خطا در حذف کیف پول.']);
        }

        return redirect()->route('merchant.wallets')->with('success', 'کیف پول با موفقیت حذف شد');
    }

    private function generateWalletAddress($currency)
    {
        // Generate a mock wallet address based on currency
        // In production, you would integrate with blockchain APIs to generate real addresses
        switch ($currency) {
            case 'BTC':
                // Bitcoin address format (P2PKH starts with 1, P2SH with 3, Segwit with bc1)
                return '1' . bin2hex(random_bytes(20));
            case 'ETH':
                // Ethereum address format
                return '0x' . bin2hex(random_bytes(20));
            case 'USDT':
                // Tether on Ethereum network (same format as ETH)
                return '0x' . bin2hex(random_bytes(20));
            default:
                return 'addr_' . bin2hex(random_bytes(16));
        }
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
                'انتقال موفق',
                $request->amount . ' ' . $senderWallet->currency . ' به ' . $receiverWallet->user->name . ' ارسال شد',
                'success',
                'fa-paper-plane'
            );

            Notification::createNotification(
                $receiverWallet->user_id,
                'دریافت انتقال',
                $request->amount . ' ' . $receiverWallet->currency . ' از ' . auth()->user()->name . ' دریافت شد',
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
