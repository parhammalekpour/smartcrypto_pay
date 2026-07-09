<!DOCTYPE html>
<html>
<head>
    <title>Merchant Payments</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📤 Create Payment Request</h1>

    @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            @foreach($errors->all() as $error)
                ❌ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('payments.store') }}">
            @csrf

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Invoice Number</label>
                <input type="text" name="invoice_number" required placeholder="e.g., INV-001" 
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Recipient Username</label>
                <input type="text" name="recipient_username" required placeholder="Enter customer username" 
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Amount</label>
                    <input type="number" step="0.00000001" name="amount" required placeholder="0.00" 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Currency</label>
                    <select name="currency" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">Select Currency</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="USDT">Tether (USDT)</option>
                    </select>
                </div>
            </div>

            <button type="submit" style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Create Link
            </button>
        </form>
    </div>

    <h2>📋 Payment Requests</h2>

    @if($payments->count() > 0)
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Invoice</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Recipient</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Amount</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Currency</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Status</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Link</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;">{{ $payment->invoice_number }}</td>
                        <td style="padding: 10px;">{{ $payment->recipient->name ?? 'Unknown' }}</td>
                        <td style="padding: 10px;">{{ number_format($payment->amount, 8) }}</td>
                        <td style="padding: 10px;">{{ $payment->currency }}</td>
                        <td style="padding: 10px;">
                            <span style="padding: 5px 10px; border-radius: 20px; background: {{ $payment->status === 'paid' ? '#d4edda' : '#fff3cd' }}; color: {{ $payment->status === 'paid' ? '#155724' : '#856404' }}; font-size: 0.85em; font-weight: bold;">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td style="padding: 10px;">
                            <a href="{{ url('/pay/' . $payment->token) }}" target="_blank" style="color: #667eea; text-decoration: none; font-weight: bold;">
                                View →
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #999;">
            <p>No payment requests created yet</p>
        </div>
    @endif
</div>

</body>
</html>