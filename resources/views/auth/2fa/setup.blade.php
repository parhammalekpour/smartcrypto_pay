@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Setup Two-Factor Authentication (TOTP)</h2>

    <p>Secret: <code>{{ $secret }}</code></p>
    <p>OTPAuth URL (for QR code generation):</p>
    <pre>{{ $otpUrl }}</pre>

    <p>Use an authenticator app (Google Authenticator, Authy) to scan the QR code generated from the OTPAuth URL or enter the secret manually.</p>

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