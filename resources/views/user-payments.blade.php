@php
use Morilog\Jalali\Jalalian;
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>Payments - Crypto Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h2 class="logo">CryptoPay</h2>

        <x-icon-button href="{{ url('/dashboard') }}" icon="🏠" text="Dashboard" variant="primary" class="w-full" />
        <x-icon-button href="{{ url('/user') }}" icon="💼" text="Wallets" variant="info" class="w-full" />
        <x-icon-button href="{{ url('/user/transfer') }}" icon="💸" text="Transfers" variant="warning" class="w-full" />
        <x-icon-button href="{{ url('/user#transactions') }}" icon="📊" text="Transactions" variant="secondary" class="w-full" />
        <x-icon-button href="{{ url('/user/payments') }}" icon="💳" text="Payments" variant="success" class="w-full" />
    </aside>

    <!-- MAIN -->
    <main class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <h3>Payment Requests</h3>
        </div>

        <div style="max-width: 900px; margin: 30px auto;">
            @if($payments->count() > 0)
                <h2>Pending Payments</h2>
                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>From Merchant</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td><strong>{{ $payment->invoice_number }}</strong></td>
                                    <td>{{ $payment->merchant->name }}</td>
                                    <td>{{ number_format($payment->amount, 8) }}</td>
                                    <td>{{ $payment->currency }}</td>
                                    <td>
                                        <span style="padding: 5px 10px; border-radius: 5px; background: {{ $payment->status === 'paid' ? '#d4edda' : '#fff3cd' }}; color: {{ $payment->status === 'paid' ? '#155724' : '#856404' }}; font-weight: bold;">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($payment->status === 'pending')
                                            <a href="{{ url('/pay/' . $payment->token) }}" style="color: #667eea; text-decoration: none; font-weight: bold;">
                                                Pay Now →
                                            </a>
                                        @else
                                            <span style="color: #999;">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 60px 20px; background: #f5f5f5; border-radius: 10px;">
                    <p style="font-size: 1.2em; color: #999;">📭 No pending payment requests</p>
                    <p style="color: #bbb; margin-top: 10px;">Merchants will send you payment requests here</p>
                </div>
            @endif
        </div>

    </main>

</div>

<script src="{{ asset('js/app.js') }}"></script>

</body>
</html>
