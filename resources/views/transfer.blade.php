@php
use Morilog\Jalali\Jalalian;
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>Transfer - Crypto Dashboard</title>
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
            <h3>Transfer Funds</h3>
        </div>

        <div style="max-width: 600px; margin: 30px auto;">
            <form method="POST" action="{{ url('/wallet/transfer') }}" style="background: #1e1e1e; padding: 30px; border-radius: 10px;">
                @csrf

                @if ($errors->any())
                    <div style="background: #ff4444; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div style="background: #44ff44; color: black; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        {{ session('success') }}
                    </div>
                @endif

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">From Wallet</label>
                    <select name="sender_wallet_id" required style="width: 100%; padding: 10px; background: #2a2a2a; color: #fff; border: 1px solid #444; border-radius: 5px;">
                        <option value="">Select a wallet</option>
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}">{{ $wallet->currency }} - {{ number_format($wallet->balance, 8) }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Recipient Wallet Address</label>
                    <input type="text" name="wallet_address" required placeholder="Enter recipient wallet address" style="width: 100%; padding: 10px; background: #2a2a2a; color: #fff; border: 1px solid #444; border-radius: 5px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Amount</label>
                    <input type="number" name="amount" step="0.00000001" required placeholder="Enter amount" style="width: 100%; padding: 10px; background: #2a2a2a; color: #fff; border: 1px solid #444; border-radius: 5px; box-sizing: border-box;">
                </div>

                <button type="submit" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 16px;">
                    Transfer
                </button>
            </form>
        </div>

    </main>

</div>

<script src="{{ asset('js/app.js') }}"></script>

</body>
</html>
