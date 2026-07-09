@php
use Morilog\Jalali\Jalalian;
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard - CryptoPay</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            color: white;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            border-bottom: 3px solid #667eea;
            padding-bottom: 1rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f5f5f5;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 0.9rem;
        }

        table td {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
        }

        table tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
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

        .empty {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        .overflow-x-auto {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>💼 Merchant Dashboard</h1>
        <p>Welcome, {{ auth()->user()->name }}</p>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <h3>Total Revenue</h3>
            <div class="value">${{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="stat-card">
            <h3>Pending Payments</h3>
            <div class="value">{{ $pendingPayments }}</div>
        </div>
        <div class="stat-card">
            <h3>Completed Payments</h3>
            <div class="value">{{ $completedPayments }}</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Create Payment Request -->
        <div class="section">
            <h2>📤 Create Payment Request</h2>

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

            <form method="POST" action="{{ route('payments.store') }}">
                @csrf

                <div class="form-group">
                    <label>Invoice Number</label>
                    <input type="text" name="invoice_number" required placeholder="e.g., INV-001">
                </div>

                <div class="form-group">
                    <label>Recipient Username</label>
                    <input type="text" name="recipient_username" required placeholder="Enter customer username">
                </div>

                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" step="0.00000001" name="amount" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency" required>
                        <option value="">Select Currency</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="USDT">Tether (USDT)</option>
                    </select>
                </div>

                <button type="submit" class="btn">Create Payment Link</button>
            </form>
        </div>

        <!-- Wallets -->
        <div class="section">
            <h2>💰 My Wallets</h2>

            @if($wallets->count() > 0)
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Currency</th>
                                <th>Balance</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($wallets as $wallet)
                                <tr>
                                    <td><strong>{{ $wallet->currency }}</strong></td>
                                    <td>{{ number_format($wallet->balance, 8) }}</td>
                                    <td style="font-size: 0.85em; color: #666;">{{ substr($wallet->wallet_address, 0, 20) }}...</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty">
                    <p>No wallets yet</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="section">
        <h2>📊 Recent Payment Requests</h2>

        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Recipient</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td><strong>{{ $payment->invoice_number }}</strong></td>
                                <td>{{ $payment->recipient->name ?? 'Unknown' }}</td>
                                <td>{{ number_format($payment->amount, 8) }}</td>
                                <td>{{ $payment->currency }}</td>
                                <td>
                                    <span class="status-badge status-{{ $payment->status }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td>{{ Jalalian::fromDateTime($payment->created_at)->format('Y/m/d') }}</td>
                                <td>
                                    <a href="{{ url('/pay/' . $payment->token) }}" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 600;">
                                        View →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">
                <p>No payment requests created yet</p>
            </div>
        @endif
    </div>
</div>

</body>
</html>
