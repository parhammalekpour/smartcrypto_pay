<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\PaymentRequest;
use App\Models\Notification;
use App\Services\CryptoPrice;

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
        )->latest()->take(10)->get() ?? collect();

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
        return view('user.wallets', compact('wallets'));
    }

    public function storeWallet(Request $request)
    {
        $request->validate([
            'currency' => 'required|in:BTC,ETH,USDT'
        ]);

        // Generate a unique wallet address
        $walletAddress = $this->generateWalletAddress($request->currency);

        Wallet::create([
            'user_id' => auth()->id(),
            'wallet_address' => $walletAddress,
            'currency' => $request->currency,
            'balance' => 0
        ]);

        return back()->with('success', 'کیف پول ' . $request->currency . ' با موفقیت ایجاد شد');
    }

    private function generateWalletAddress($currency)
    {
        // Generate a unique wallet address based on currency
        $prefix = match($currency) {
            'BTC' => '1',
            'ETH' => '0x',
            'USDT' => '0x',
            default => '1'
        };

        do {
            $address = $prefix . bin2hex(random_bytes($currency === 'BTC' ? 20 : 18));
        } while (Wallet::where('wallet_address', $address)->exists());

        return $address;
    }

    public function send()
    {
        $wallets = auth()->user()->wallets ?? collect();
        return view('user.send', compact('wallets'));
    }

    public function receive()
    {
        $wallets = auth()->user()->wallets ?? collect();
        return view('user.receive', compact('wallets'));
    }

    public function transactions(Request $request)
    {
        $wallets = auth()->user()->wallets ?? collect();
        
        $query = Transaction::whereIn('wallet_id', $wallets->pluck('id'));

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

        $transactions = $query->with(['paymentRequest.merchant', 'sender', 'recipient', 'wallet'])->latest()->paginate(20);
        
        return view('user.transactions', compact('transactions', 'wallets'));
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

        auth()->user()->update($updateData);

        return redirect()->route('user.settings')->with('success', 'تنظیمات بروزرسانی شد');
    }

    public function logoutAllDevices(Request $request)
    {
        // Delete all tokens for this user
        auth()->user()->tokens()->delete();
        
        // Logout current session
        auth()->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'از تمام دستگاه ها خارج شدید');
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
            'سپرده تجربی',
            $amount . ' ' . $wallet->currency . ' به کیف پول شما اضافه شد',
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
            ]);
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
        $cryptoPrice = new CryptoPrice();
        
        return response()->json([
            'btc' => $cryptoPrice->getPrice('BTC'),
            'eth' => $cryptoPrice->getPrice('ETH'),
            'usd' => 1,
            'timestamp' => now()->getTimestamp()
        ]);
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
            'درخواست رد شد',
            $payment->invoice_number . ' توسط ' . auth()->user()->name . ' رد شد',
            'danger',
            'fa-times-circle'
        );

        return back()->with('success', 'درخواست پرداخت با موفقیت رد شد');
    }
}
