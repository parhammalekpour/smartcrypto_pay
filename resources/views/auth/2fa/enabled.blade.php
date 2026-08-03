@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Two-Factor Authentication Enabled</h2>

    <p>Two-factor authentication has been enabled for your account. Below are your one-time backup codes. Save them in a secure place — each code can be used once to regain access if you lose your authenticator device.</p>

    <div style="margin-top:16px;">
        <ul style="list-style:none;padding:0;">
            @foreach($codes as $code)
                <li style="display:inline-block;margin:6px;padding:10px 12px;border:1px dashed #ccc;border-radius:6px;background:#fff;font-family:monospace;">{{ $code }}</li>
            @endforeach
        </ul>
    </div>

    <div style="margin-top:20px;">
        <a href="{{ route('user.settings') }}" class="btn btn-primary">بازگشت به تنظیمات</a>
    </div>
</div>
@endsection