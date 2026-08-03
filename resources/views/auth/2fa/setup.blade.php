@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Setup Two-Factor Authentication (TOTP)</h2>

    <p>Secret: <code>{{ $secret }}</code></p>
    <p>Scan the QR code below with an authenticator app (Google Authenticator, Authy) or enter the secret manually:</p>

    <div style="margin:12px 0;">
        <img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl={{ urlencode($otpUrl) }}" alt="QR Code">
    </div>

    <p style="font-size:0.9em;color:#666;">If the QR code doesn't work, enter the secret into your authenticator app manually.</p>

    <form method="POST" action="{{ route('2fa.enable') }}">
        @csrf
        <input type="hidden" name="secret" value="{{ $secret }}">
        <div class="form-group">
            <label for="code">Enter code from authenticator app</label>
            <input id="code" name="code" required class="form-control" />
        </div>
        <button class="btn btn-primary">Enable 2FA</button>
    </form>
</div>
@endsection