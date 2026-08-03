<x-guest-layout>
    <div class="mb-8 text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">CryptoPay</p>
        <h1 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-white">ثبت نام در CryptoPay</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400 max-w-md mx-auto">یک حساب کاربری امن بسازید و به دنیای پرداخت‌های سریع و هوشمند رمزارز دسترسی پیدا کنید.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
 
        <div class="mt-4">
            <x-input-label for="role" value="نوع حساب" />
            <select id="role" name="role" class="block mt-1 w-full border border-slate-300 dark:border-slate-600 dark:bg-gray-700 dark:text-gray-900 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm transition-colors duration-300 px-4 py-2 text-sm">
                <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Personal</option>
                <option value="merchant" {{ old('role') === 'merchant' ? 'selected' : '' }}>Business</option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>
 
        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 space-y-4 sm:flex sm:items-center sm:justify-between sm:space-y-0">
            <a class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white transition rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="w-full sm:w-auto">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
