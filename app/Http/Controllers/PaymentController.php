<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Customer;
use App\Models\PaymentRequest;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\Rule;

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
 
        $customers = auth()->user()->customers()->orderBy('name')->get();

        // Compute next invoice number for convenience (prefill, editable)
        $nextInvoiceNumber = 'INV-001';

        // Base the sequence on the last PAID (settled) invoice — unpaid/draft invoices do not advance the sequence
        $lastPaid = PaymentRequest::where('merchant_id', auth()->id())
            ->where('status', 'paid')
            ->orderByDesc('updated_at')
            ->first();

        $baseCandidate = null;

        if ($lastPaid && $lastPaid->invoice_number) {
            $baseCandidate = $lastPaid->invoice_number;
        }

        if ($baseCandidate) {
            // If invoice ends with digits, increment that numeric suffix and preserve padding
            if (preg_match('/(\d+)$/', $baseCandidate, $matches)) {
                $lastNum = intval($matches[1]);
                $pad = max(3, strlen($matches[1]));
                $nextNum = $lastNum + 1;
                $padded = str_pad((string)$nextNum, $pad, '0', STR_PAD_LEFT);
                $candidate = preg_replace('/(\d+)$/', $padded, $baseCandidate);
            } else {
                // no trailing digits — append a standard suffix
                $candidate = $baseCandidate . '-001';
            }

            // Ensure the generated candidate is unique for this merchant — skip over existing invoice_numbers (including unpaid ones)
            while (PaymentRequest::where('merchant_id', auth()->id())->where('invoice_number', $candidate)->exists()) {
                // increment numeric suffix further
                if (preg_match('/(\d+)$/', $candidate, $m2)) {
                    $num = intval($m2[1]) + 1;
                    $pad2 = max(3, strlen($m2[1]));
                    $candidate = preg_replace('/(\d+)$/', str_pad((string)$num, $pad2, '0', STR_PAD_LEFT), $candidate);
                } else {
                    // unlikely, but append -001 then continue loop
                    $candidate = $candidate . '-001';
                }
            }

            $nextInvoiceNumber = $candidate;
        }

        return view('merchant.payments', compact('payments', 'pendingCount', 'paidCount', 'customers', 'nextInvoiceNumber'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isKycVerified()) {
            return back()->withErrors(['error' => 'برای ایجاد درگاه یا درخواست پرداخت باید احراز هویت KYC شما تایید شده باشد.'])->withInput();
        }

        $request->validate([
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                // Ensure invoice number is unique for this merchant
                Rule::unique('payment_requests')->where(function ($query) {
                    return $query->where('merchant_id', auth()->id());
                }),
            ],
            'amount' => 'required|numeric|min:0.00000001',
            'currency' => 'required',
            'recipient_username' => 'required|string|max:255',
        ], [
            'invoice_number.unique' => 'شماره فاکتور تکراری است. لطفاً شماره دیگری وارد کنید.'
        ]);
 
        $recipient = User::where('name', $request->recipient_username)->first();

        if (!$recipient) {
            return back()->withErrors(['recipient_username' => 'نام کاربری گیرنده یافت نشد یا باید در سیستم ثبت شده باشد']);
        }

        $customer = Customer::where('merchant_id', auth()->id())
            ->where('user_id', $recipient->id)
            ->first();

        PaymentRequest::create([
            'merchant_id' => auth()->id(),
            'recipient_user_id' => $recipient->id,
            'customer_id' => $customer ? $customer->id : null,
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

    public function pay(Request $request, $token)
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

        if (!auth()->user()->isKycVerified()) {
           return back()->withErrors(['payment' => 'برای انجام پرداخت باید احراز هویت KYC شما تایید شده باشد.'])->withInput();
        }

        if (!$payment->merchant?->isKycVerified()) {
           return back()->withErrors(['payment' => 'فروشنده هنوز احراز هویت KYC خود را تکمیل نکرده است. پرداخت امکان‌پذیر نیست.'])->withInput();
        }

        // For gateway payments, only KYC verification is required. 2FA is not enforced here.
        // Note: other flows (e.g., sensitive account actions) may still require 2FA elsewhere in the application.


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
                'sender_id' => auth()->id(),
                'recipient_id' => $payment->merchant_id,
                'type' => 'payment',
                'amount' => $payment->amount,
                'status' => 'completed',
                'description' => 'Invoice Payment',
                'reference' => (preg_match('/^INV-/i', $payment->invoice_number) ? $payment->invoice_number : ('INV-' . $payment->invoice_number)) ,
                'payment_request_id' => $payment->id
            ]);

            // تراکنش merchant
            Transaction::create([
                'wallet_id' => $merchantWallet->id,
                'sender_id' => auth()->id(),
                'recipient_id' => $payment->merchant_id,
                'type' => 'deposit',
                'amount' => $payment->amount,
                'status' => 'completed',
                'description' => 'Payment Received',
                'reference' => (preg_match('/^INV-/i', $payment->invoice_number) ? $payment->invoice_number : ('INV-' . $payment->invoice_number)) ,
                'payment_request_id' => $payment->id
            ]);

            // Notification for customer (user who paid)
            \App\Models\Notification::createNotification(
                $payment->recipient_user_id,
                __('notifications.payment_paid.title'),
                __('notifications.payment_paid.message', [
                    'amount' => number_format($payment->amount, 8),
                    'currency' => $payment->currency,
                    'merchant' => $payment->merchant->name,
                ]),
                'success',
                'fa-check-circle'
            );

            // Notification for merchant (who received payment)
            \App\Models\Notification::createNotification(
                $payment->merchant_id,
                __('notifications.payment_received.title'),
                __('notifications.payment_received.message', [
                    'amount' => number_format($payment->amount, 8),
                    'currency' => $payment->currency,
                    'customer' => $payment->recipient->name,
                ]),
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