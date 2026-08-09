<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Invoice {{ $data['invoice_number'] ?? $data['id'] }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color:#111; }
        .container { max-width:800px; margin:24px auto; padding:16px; border:1px solid #e5e7eb; border-radius:6px; }
        .header { display:flex; justify-content:space-between; align-items:center; }
        .meta { margin-top:16px; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { text-align:right; padding:8px; border-bottom:1px solid #f3f4f6; }
        .amount { font-weight:700; font-size:1.2rem }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h2>فاکتور فروش</h2>
            <div style="color:#6b7280">شماره فاکتور: {{ $data['invoice_number'] ?? $data['id'] }}</div>
        </div>
        <div style="text-align:left">
            <strong>CryptoPay</strong>
            <div style="color:#6b7280">تاریخ: {{ $data['created_at'] }}</div>
            @if($data['paid_at'])
                <div style="color:#10b981">تاریخ پرداخت: {{ $data['paid_at'] }}</div>
            @endif
        </div>
    </div>

    <div class="meta">
        <table>
            <tr>
                <th>توضیح</th>
                <td>{{ $data['description'] }}</td>
            </tr>
            <tr>
                <th>مبلغ</th>
                <td class="amount">{{ $data['amount'] }} {{ $data['currency'] }}</td>
            </tr>
            <tr>
                <th>وضعیت</th>
                <td>{{ $data['status'] }}</td>
            </tr>
            <tr>
                <th>گیرنده</th>
                <td>{{ $data['recipient'] }}</td>
            </tr>
        </table>
    </div>

    <div style="margin-top:18px;color:#6b7280;font-size:0.9rem">
        این فاکتور یک خروجی ساده برای دانلود است. برای صدور فاکتور رسمی با سربرگ شرکت و قالب چاپی، لازم است قالب PDF/HTML اختصاصی به سیستم اضافه شود.
    </div>
</div>
</body>
</html>