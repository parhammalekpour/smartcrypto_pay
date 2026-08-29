@props([
    'email' => auth()->user()?->email ?? '',
    'resendRoute' => route('verification.send', absolute: false),
    'logoutRoute' => route('logout', absolute: false),
    'changeEmailRoute' => route('profile.edit', absolute: false),
])

<div
    class="w-full max-w-[560px]"
    x-data="{
        email: @js($email),
        sending: false,
        countdown: 0,
        timer: null,
        successMessage: '',
        errorMessage: '',
        copied: false,
        startCountdown() {
            this.countdown = 45;
            if (this.timer) {
                clearInterval(this.timer);
            }
            this.timer = setInterval(() => {
                if (this.countdown > 0) {
                    this.countdown -= 1;
                    return;
                }
                clearInterval(this.timer);
                this.timer = null;
            }, 1000);
        },
        async copyEmail() {
            if (!this.email) {
                return;
            }
            try {
                await navigator.clipboard.writeText(this.email);
                this.copied = true;
                setTimeout(() => this.copied = false, 1600);
            } catch (error) {
                this.copied = false;
            }
        },
        async resendVerification() {
            if (this.sending || this.countdown > 0) {
                return;
            }
            this.sending = true;
            this.successMessage = '';
            this.errorMessage = '';

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const form = this.$refs.resendForm;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                if (!response.ok) {
                    throw new Error('Failed to resend verification email');
                }

                this.startCountdown();
                this.successMessage = 'A verification email has been sent successfully.';
                this.errorMessage = '';
            } catch (error) {
                this.successMessage = '';
                this.errorMessage = 'We could not resend the verification email. Please try again.';
            } finally {
                this.sending = false;
            }
        }
    }"
    x-init="if ('{{ session('status') }}' === 'verification-link-sent') { startCountdown(); }"
>
    <div class="w-full">
        <div class="relative overflow-hidden rounded-[32px] border border-slate-700/80 bg-[#0B1220]/90 shadow-[0_30px_90px_rgba(2,6,23,0.72)] backdrop-blur-xl">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(96,165,250,0.10),_transparent_18%)]"></div>
            <div class="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-sky-500/5 to-transparent"></div>

            <div class="relative p-5 sm:p-8">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center rounded-full border border-sky-400/20 bg-sky-500/10 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.28em] text-sky-200">
                            CryptoPay
                        </div>
                        <h1 class="mt-5 text-3xl font-black text-white">Verify your email</h1>
                    </div>

                    <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl border border-sky-400/30 bg-gradient-to-br from-sky-500/15 to-blue-600/20 text-sky-200 shadow-[0_12px_24px_rgba(56,189,248,0.12)]">
                        <span class="absolute inset-0 rounded-2xl bg-sky-400/10 blur-md"></span>
                        <svg class="relative h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 8.5A2.5 2.5 0 0 1 5.5 6h13A2.5 2.5 0 0 1 21 8.5v7A2.5 2.5 0 0 1 18.5 18h-13A2.5 2.5 0 0 1 3 15.5v-7Z" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4 7.5 12 13l8-5.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>

                <div class="rounded-[28px] border border-slate-700/80 bg-slate-950/40 p-4 sm:p-5">
                    <p class="text-center text-[15px] leading-8 text-slate-300">
                        Before continuing, please verify your email address. We sent a <span class="font-semibold text-slate-100">verification link</span> to your inbox. If it is not there, please check your <span class="font-semibold text-slate-100">Inbox</span> and <span class="font-semibold text-slate-100">Spam</span> folder.
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <div class="mt-5 flex items-start gap-3 rounded-[16px] border border-sky-400/20 bg-sky-500/10 px-3.5 py-3 text-sm text-sky-100" aria-live="polite">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 11v5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 7h.01" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>A fresh verification link has been sent to your email.</span>
                        </div>
                    @endif

                    <div
                        x-show="successMessage"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mt-5 flex items-center gap-3 rounded-[16px] border border-emerald-400/20 bg-emerald-500/10 px-3.5 py-3 text-sm text-emerald-100"
                        role="status"
                        aria-live="polite"
                    >
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7.75 12.5 10.5 15.25 16.25 9.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-text="successMessage"></span>
                    </div>

                    <div
                        x-show="errorMessage"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mt-5 flex items-center gap-3 rounded-[16px] border border-red-400/20 bg-red-500/10 px-3.5 py-3 text-sm text-red-100"
                        role="alert"
                        aria-live="assertive"
                    >
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="m9.5 9.5 5 5M14.5 9.5l-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-text="errorMessage"></span>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[20px] border border-blue-500/20 bg-[#0E1729]">
                        <div class="flex items-center gap-3 border-l-2 border-blue-500 bg-blue-500/5 px-3 py-3 sm:px-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-slate-900/80 text-slate-300">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 8.5A2.5 2.5 0 0 1 5.5 6h13A2.5 2.5 0 0 1 21 8.5v7A2.5 2.5 0 0 1 18.5 18h-13A2.5 2.5 0 0 1 3 15.5v-7Z" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 7.5 12 13l8-5.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1 text-center">
                                <span class="block truncate text-[15px] font-medium text-white">{{ $email ?: 'name@company.com' }}</span>
                            </div>

                            <button
                                type="button"
                                @click="copyEmail()"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-slate-900/80 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-blue-400/40 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-[#0E1729]"
                                :aria-label="copied ? 'Email copied' : 'Copy email'"
                            >
                                <svg x-show="!copied" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M9 9.5A2.5 2.5 0 0 1 11.5 7h5A2.5 2.5 0 0 1 19 9.5v5A2.5 2.5 0 0 1 16.5 17h-5A2.5 2.5 0 0 1 9 14.5v-5Z" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5 14.5A2.5 2.5 0 0 1 7.5 12H9v2.5A2.5 2.5 0 0 1 6.5 17H5A2.5 2.5 0 0 1 2.5 14.5v-5A2.5 2.5 0 0 1 5 7h2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg x-show="copied" x-transition class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M5 12.5 9.5 17 19 7.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 rounded-[16px] border border-sky-400/20 bg-sky-500/5 px-3.5 py-3 text-sm text-sky-100">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 11v5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 7h.01" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>If you do not see the email in your inbox, please check your <span class="font-semibold">Spam</span> folder as well.</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ $resendRoute }}" x-ref="resendForm" x-on:submit.prevent="resendVerification()" class="mt-6">
                        @csrf
                        <button
                            type="submit"
                            :disabled="sending || countdown > 0"
                            :aria-disabled="sending || countdown > 0"
                            :aria-busy="sending"
                            class="group relative inline-flex h-[58px] w-full items-center justify-center overflow-hidden rounded-[16px] bg-gradient-to-r from-sky-500 via-blue-600 to-violet-600 px-5 text-[18px] font-semibold text-white shadow-[0_16px_34px_rgba(59,130,246,0.26)] transition hover:brightness-110 active:translate-y-[0.5px] focus:outline-none focus:ring-2 focus:ring-sky-400/50 disabled:cursor-not-allowed disabled:opacity-80"
                        >
                            <span class="absolute inset-0 bg-white/10 opacity-0 transition duration-200 group-hover:opacity-100"></span>
                            <span class="relative inline-flex items-center gap-3">
                                <svg x-show="sending" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"/>
                                    <path d="M21 12a9 9 0 0 1-9 9" stroke-linecap="round"/>
                                </svg>
                                <svg x-show="!sending && countdown == 0" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M20 7v6h-6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M20 13a8 8 0 1 1-2.34-5.66L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span x-text="sending ? 'Sending...' : countdown > 0 ? 'Resend in ' + countdown + 's' : 'Resend email'" class="whitespace-nowrap"></span>
                            </span>
                        </button>
                    </form>

                    <div x-show="countdown > 0" x-transition class="mt-4 text-center text-[13px] text-slate-400">
                        <span>You can try again in <span x-text="countdown"></span> seconds.</span>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-3 border-t border-slate-700/80 pt-4 text-[15px]">
                        <form method="POST" action="{{ $logoutRoute }}" class="inline-block">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 font-medium text-[#ff6b6b] transition hover:text-[#ff8a8a]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M16 17 21 12 16 7" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21 12H9" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Log out</span>
                            </button>
                        </form>

                        <span class="h-5 w-px bg-slate-600"></span>

                        <a href="{{ $changeEmailRoute }}" class="inline-flex items-center gap-2 font-medium text-sky-300 transition hover:text-sky-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 20h9" stroke-linecap="round"/>
                                <path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Change email</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-5 flex flex-col items-center justify-center gap-2 text-[12px] text-slate-400">
            <div class="flex items-center gap-4">
                <a href="#" class="transition hover:text-slate-200">Terms of Service</a>
                <span class="text-slate-500">•</span>
                <a href="#" class="transition hover:text-slate-200">Privacy Policy</a>
            </div>
            <div class="mt-1 text-[11px] tracking-[0.18em] text-slate-400/80">
                System version {{ app()->version() }}
            </div>
        </footer>
    </div>
</div>
