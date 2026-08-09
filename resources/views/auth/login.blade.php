<x-guest-layout>
    <div class="mb-8 text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">CryptoPay</p>
        <h1 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-white">{{ __('Login to your account') }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400 max-w-md mx-auto">{{ __('Login description') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="mt-4 sm:flex sm:items-center sm:justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500" name="remember">
                <span class="ms-2 text-sm text-slate-600 dark:text-slate-300">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-sky-600 hover:text-sky-700 transition rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
