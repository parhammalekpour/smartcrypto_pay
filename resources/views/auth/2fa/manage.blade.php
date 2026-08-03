@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Two-Factor Authentication</h2>

    @if(isset($two) && $two->enabled_at)
        <p>2FA is enabled.</p>
        <form method="POST" action="{{ route('2fa.disable') }}">
            @csrf
            <button class="btn btn-danger">Disable 2FA</button>
        </form>
    @else
        <p>2FA is not enabled. Go to setup to enable.</p>
    @endif
</div>
@endsection