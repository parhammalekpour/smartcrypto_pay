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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
    <div class="header" x-data="{ notificationOpen: false, notificationCount: 0, notifications: [] }" x-init="(()=>{ try { window.NOTIFICATION_ENDPOINTS = {
            list: '{{ LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications') }}',
            unread: '{{ LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications/unread-count') }}',
            markAll: '{{ LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications/mark-all-read') }}',
            base: '{{ LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications') }}'
        };
            // Seed initial notifications server-side to avoid client fetch-order race
            @auth
                window.INITIAL_NOTIFICATIONS = {!! json_encode(
                    App\Models\Notification::where('user_id', auth()->id())->orderBy('id', 'desc')->take(5)->get()->map(function($n){
                        return [
                            'id' => $n->id,
                            'title' => App\Models\Notification::localizeText($n->title),
                            'message' => App\Models\Notification::localizeText($n->message),
                            'icon' => $n->icon,
                            'type' => $n->type,
                            'read' => (bool) $n->read,
                            'created_at' => $n->created_at->diffForHumans(),
                        ];
                    })
                ) !!};
            @else
                window.INITIAL_NOTIFICATIONS = null;
            @endauth

            const component = this;
            try { if (window.INITIAL_NOTIFICATIONS && Array.isArray(window.INITIAL_NOTIFICATIONS)) { component.notifications = window.INITIAL_NOTIFICATIONS; component.notificationCount = component.notifications.filter(n => !n.read).length; } } catch(e){}

            const updateUnread = async () => {
                console.debug('[Notifications] updateUnread called (merchant)');
                try {
                    console.debug('[Notifications] requesting unread (merchant):', window.NOTIFICATION_ENDPOINTS.unread);
                    const res = await fetch(window.NOTIFICATION_ENDPOINTS.unread, {credentials: 'same-origin'});
                    console.debug('[Notifications] unread response (merchant):', res);
                    const data = await res.json();
                    console.debug('[Notifications] unread data (merchant):', data);
                    const newCount = Number(data.count || 0);

                    if (typeof window.__notificationLastCount === 'undefined') {
                        window.__notificationLastCount = newCount;
                    }

                    if (newCount > (window.__notificationLastCount || 0)) {
                        try {
                            const listRes = await fetch(window.NOTIFICATION_ENDPOINTS.list, {credentials: 'same-origin'});
                            const listData = await listRes.json();
                            console.log('Notifications fetched (merchant on delta):', listData);

                            try {
                                const existingIds = new Set((component.notifications || []).map(n => n.id));
                                const newItems = (listData || []).filter(n => !existingIds.has(n.id));
                                if (newItems.length > 0) {
                                    component.notifications = newItems.concat(component.notifications || []);
                                    if (component.notifications.length > 50) component.notifications = component.notifications.slice(0, 50);
                                    try { component.notificationCount = component.notifications.filter(n => !n.read).length; } catch(e) { component.notificationCount = newCount; }
                                    try { if (typeof window.showToast === 'function') { newItems.forEach(i=>window.showToast(i.title||'Notification', i.message||'')); } } catch(e){}
                                } else {
                                    component.notificationCount = newCount;
                                }
                            } catch(e) {
                                component.notifications = listData;
                                try { component.notificationCount = component.notifications.filter(n => !n.read).length; } catch(e) { component.notificationCount = newCount; }
                            }
                        } catch(err) {
                            console.error('Notifications fetch error (merchant on delta):', err);
                            component.notificationCount = newCount;
                        }
                    } else {
                        component.notificationCount = newCount;
                    }

                    window.__notificationLastCount = newCount;
                } catch(e) {}
            };

            updateUnread();
            fetch(window.NOTIFICATION_ENDPOINTS.list, {credentials: 'same-origin'}).then(r => r.json()).then(data => { console.log('Notifications fetched (merchant):', data); component.notifications = data; try { component.notificationCount = component.notifications.filter(n => !n.read).length; } catch(e){} }).catch(err=>{ console.error('Notifications fetch error (merchant):', err); });

            if (!window.__notificationPollStarted) { window.__notificationPollStarted = true; setInterval(updateUnread, 5000); }
                    } catch(e) { console.error('Notifications init error (merchant):', e); }
                    })()">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h1>💼 Merchant Dashboard</h1>
                <p>Welcome, {{ auth()->user()->name }}</p>
            </div>
            <div style="position:relative;">
                <button @click="notificationOpen = !notificationOpen; if (notificationOpen) { fetch(window.NOTIFICATION_ENDPOINTS.list, {credentials: 'same-origin'}).then(r=>r.json()).then(data=>{ notifications = data; try{ notificationCount = notifications.filter(n => !n.read).length;}catch(e){} }).catch(()=>{}); }" style="background:transparent;border:none;color:white;font-size:20px;cursor:pointer;">
                    <i class="fas fa-bell"></i>
                    <span x-show="notificationCount > 0" x-text="notificationCount" style="background:#ef4444;color:white;border-radius:999px;padding:2px 6px;font-size:12px;margin-left:6px;position:relative;top:-8px;left:-6px;"></span>
                </button>

                <div x-show="notificationOpen" @click.away="notificationOpen=false" style="position:absolute;top:calc(100% + 0.5rem);width:min(24rem, calc(100vw - 2rem));max-width:calc(100vw - 2rem);max-height:min(80vh, 32rem);overflow-y:auto;background:white;color:#111;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:999;left:{{ app()->getLocale() === 'fa' ? '0' : 'auto' }};right:{{ app()->getLocale() === 'fa' ? 'auto' : '0' }};direction:{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }};">
                    <div style="padding:12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <strong>{{ __('Notifications') }}</strong>
                        <button @click="fetch(window.NOTIFICATION_ENDPOINTS.markAll, {method:'POST', credentials:'same-origin', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>{ notificationOpen=false; notifications=[]; fetch(window.NOTIFICATION_ENDPOINTS.unread,{credentials:'same-origin'}).then(r=>r.json()).then(d=>notificationCount=d.count); }).catch(()=>{});" style="background:transparent;border:none;color:#667eea;cursor:pointer;">{{ __('Mark all as read') }}</button>
                    </div>
                    <template x-if="notifications.length === 0">
                        <div style="padding:20px;text-align:center;color:#666;">{{ __('No notifications') }}</div>
                    </template>
                    <template x-for="notification in notifications" :key="notification.id">
                        <div style="padding:12px;border-bottom:1px solid #f3f3f3;display:flex;gap:10px;align-items:flex-start;">
                            <div style="font-size:18px;color:#3b82f6;flex-shrink:0;"> <i :class="'fas ' + notification.icon"></i></div>
                            <div style="flex:1;min-width:0;overflow-wrap:anywhere;word-break:break-word;white-space:normal;">
                                <div style="font-weight:600;" x-text="notification.title"></div>
                                <div style="font-size:13px;color:#555;margin-top:4px;" x-text="notification.message"></div>
                                <div style="font-size:11px;color:#999;margin-top:6px;" x-text="notification.created_at"></div>
                            </div>
                            <div style="flex-shrink:0;">
                                <button @click="fetch(window.NOTIFICATION_ENDPOINTS.base + '/' + notification.id + '/delete', {method:'POST', credentials:'same-origin', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>{ notifications = notifications.filter(n=>n.id!==notification.id); fetch(window.NOTIFICATION_ENDPOINTS.unread,{credentials:'same-origin'}).then(r=>r.json()).then(d=>notificationCount=d.count); }).catch(()=>{});" style="background:transparent;border:none;color:#999;cursor:pointer;"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
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
                                    <td>{{ \App\Support\NumberHelper::formatCryptoAmount($wallet->display_balance) }}</td>
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
                                <td>{{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }}</td>
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
