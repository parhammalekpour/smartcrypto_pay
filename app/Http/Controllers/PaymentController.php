<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\PaymentRequest;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;

class PaymentController extends Controller
{
    public function index()
    {
        $query = PaymentRequest::where('merchant_id', auth()->id());

        // فیلتر جستجو
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('recipient', function ($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  });
            });
        }

        // فیلتر وضعیت
        if (request('status')) {
            $query->where('status', request('status'));
        }

        // فیلتر ارز
        if (request('currency')) {
            $query->where('currency', request('currency'));
        }

        $payments = $query->latest()->paginate(15);

        $pendingCount = PaymentRequest::where('merchant_id', auth()->id())
            ->where('status', 'pending')
            ->count();

        $paidCount = PaymentRequest::where('merchant_id', auth()->id())
            ->where('status', 'paid')
            ->count();

        return view('merchant.payments', compact('payments', 'pendingCount', 'paidCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required',
            'amount' => 'required|numeric|min:0.00000001',
            'currency' => 'required',
            'recipient_username' => 'required|exists:users,name'
        ]);

        $recipient = User::where('name', $request->recipient_username)->first();

        PaymentRequest::create([
            'merchant_id' => auth()->id(),
            'recipient_user_id' => $recipient->id,
            'invoice_number' => $request->invoice_number,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'token' => Str::random(32),
            'status' => 'pending'
        ]);

        return back()->with(
            'success',
            'Payment link created successfully'
        );
    }

    public function show($token)
    {
        $payment = PaymentRequest::where(
            'token',
            $token
        )->firstOrFail();

        return view('payment-page', compact('payment'));
    }

    public function cancel($id)
    {
        $payment = PaymentRequest::findOrFail($id);
        
        // Verify merchant owns this payment request
        if ($payment->merchant_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Only pending payments can be cancelled
        if ($payment->status !== 'pending') {
            return back()->withErrors(['error' => 'فقط درخواست‌های در انتظار قابل لغو هستند']);
        }

        $payment->update(['status' => 'cancelled']);

        return back()->with('success', 'درخواست پرداخت با موفقیت لغو شد');
    }

    public function pay($token)
    {
        $payment = PaymentRequest::where(
            'token',
            $token
        )->firstOrFail();

        // جلوگیری از پرداخت دوباره
        if ($payment->status === 'paid') {
            return redirect()
                ->route('payment.show', $payment->token)
                ->withErrors([
                    'payment' => 'This invoice is already paid'
                ]);
        }

        // کیف پول کاربر
        $customerWallet = Wallet::where(
            'user_id',
            auth()->id()
        )
        ->where(
            'currency',
            $payment->currency
        )
        ->first();

        if (!$customerWallet) {
            return back()->withErrors([
                'payment' => 'Customer wallet not found'
            ]);
        }

        if ($customerWallet->balance < $payment->amount) {
            return back()->withErrors([
                'payment' => 'Insufficient balance'
            ]);
        }

        // کیف پول merchant (اگر نبود ساخته می‌شود)
        $merchantWallet = Wallet::firstOrCreate(
            [
                'user_id' => $payment->merchant_id,
                'currency' => $payment->currency,
            ],
            [
                'wallet_address' => strtoupper($payment->currency)
                    . '-MERCHANT-'
                    . uniqid(),

                'balance' => 0
            ]
        );

        // جلوگیری از انتقال به خود
        if ($customerWallet->id === $merchantWallet->id) {
            return back()->withErrors([
                'payment' => 'Invalid wallet routing'
            ]);
        }

        DB::transaction(function () use (
            $customerWallet,
            $merchantWallet,
            $payment
        ) {

            // کم کردن از کاربر
            $customerWallet->decrement(
                'balance',
                $payment->amount
            );

            // اضافه کردن به merchant
            $merchantWallet->increment(
                'balance',
                $payment->amount
            );

            // تغییر وضعیت پرداخت
            $payment->update([
                'status' => 'paid'
            ]);

            // تراکنش کاربر
            Transaction::create([
                'wallet_id' => $customerWallet->id,
                'type' => 'payment',
                'amount' => $payment->amount,
                'status' => 'completed',
                'description' => 'Invoice Payment',
                'reference' => 'INV-' . $payment->invoice_number,
                'payment_request_id' => $payment->id
            ]);

            // تراکنش merchant
            Transaction::create([
                'wallet_id' => $merchantWallet->id,
                'type' => 'deposit',
                'amount' => $payment->amount,
                'status' => 'completed',
                'description' => 'Payment Received',
                'reference' => 'INV-' . $payment->invoice_number,
                'payment_request_id' => $payment->id
            ]);

            // Notification for customer (user who paid)
            \App\Models\Notification::createNotification(
                $payment->recipient_user_id,
                '✅ پرداختی شد',
                'شما ' . number_format($payment->amount, 8) . ' ' . $payment->currency . ' به ' . $payment->merchant->name . ' پرداخت کردید',
                'success',
                'fa-check-circle'
            );

            // Notification for merchant (who received payment)
            \App\Models\Notification::createNotification(
                $payment->merchant_id,
                '✅ پرداخت دریافت شد',
                'شما ' . number_format($payment->amount, 8) . ' ' . $payment->currency . ' از ' . $payment->recipient->name . ' دریافت کردید',
                'success',
                'fa-inbox'
            );
        });

        return redirect()
            ->route('user.pending-payments')
            ->with(
                'success',
                'Payment successful! Transaction confirmed (demo)'
            );
    }
}