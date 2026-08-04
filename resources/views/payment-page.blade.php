<!DOCTYPE html>
<html>
<head>
    <title>Payment Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .payment-card h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.8em;
        }

        .payment-card p {
            color: #666;
            margin-bottom: 5px;
        }

        .invoice-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            border-left: 4px solid #667eea;
        }

        .invoice-info p {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .invoice-info .label {
            color: #999;
            font-size: 0.9em;
            font-weight: normal;
        }

        .amount-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }

        .amount-box .amount {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .amount-box .currency {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .status-box {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="payment-card">
    <h1>💳 Crypto Payment</h1>

    @if(session('success'))
        <div class="alert alert-success">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
                ❌ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="invoice-info">
        <p><span class="label">Invoice Number</span><br><strong>{{ $payment->invoice_number }}</strong></p>
        <p style="margin-top: 15px;"><span class="label">From Merchant</span><br><strong>{{ $payment->merchant->name }}</strong></p>
    </div>

    <div class="amount-box">
        <div class="amount">{{ number_format($payment->amount, 8) }}</div>
        <div class="currency">{{ $payment->currency }}</div>
    </div>

    <div class="status-box status-{{ $payment->status }}">
        Status: {{ ucfirst($payment->status) }}
    </div>

    @if($payment->status === 'pending')
        <form method="POST" action="{{ route('payment.pay', $payment->token) }}">
            @csrf

                    @php
                        $userKyc = auth()->check() ? auth()->user()->kyc_verified : false;
                        $merchantKyc = $payment->merchant?->kyc_verified ?? false;
                        $canPay = $userKyc && $merchantKyc;
                    @endphp

                    @if(!$merchantKyc)
                        <div class="alert alert-error">
                            فروشنده این تراکنش هنوز احراز هویت KYC خود را تکمیل نکرده است. پرداخت فعلاً امکان‌پذیر نیست.
                        </div>
                    @endif

                    @if(auth()->check() && !$userKyc)
                        <div class="alert alert-error">
                            قبل از پرداخت، باید احراز هویت KYC خود را تکمیل کنید.
                        </div>
                    @endif

                    <button type="submit" class="btn" @if(!$canPay) disabled @endif>
                        Pay Now
                    </button>
        </form>
    @else
        <p style="text-align: center; color: #999; margin-top: 20px;">
            ✅ This invoice has already been paid
        </p>
    @endif
</div>

</body>
</html>