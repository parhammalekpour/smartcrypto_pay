@extends('layouts.dashboard')

@section('title', 'تنظیمات')
@section('page-title', 'تنظیمات')
@section('page-subtitle', 'مدیریت تنظیمات حساب و امنیت')

@section('content')
<div class="max-w-6xl">
    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-800 rounded">
            <ul class="text-red-700 dark:text-red-200 text-sm font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Success Messages -->
    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 dark:border-green-800 rounded">
            <p class="text-green-700 dark:text-green-200 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Settings Container -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-300">
        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <button onclick="switchTab('general')" id="tab-general" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-700 dark:text-gray-300 border-b-4 border-indigo-600 dark:border-indigo-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition active-tab">
                <i class="fas fa-user ml-2"></i>عمومی
            </button>
            <button onclick="switchTab('security')" id="tab-security" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i class="fas fa-lock ml-2"></i>امنیت
            </button>
            <button onclick="switchTab('privacy')" id="tab-privacy" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i class="fas fa-shield-alt ml-2"></i>حریم خصوصی
            </button>
            <button onclick="switchTab('kyc')" id="tab-kyc" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i class="fas fa-id-card ml-2"></i>احراز هویت (KYC)
            </button>
        </div>

        <!-- Tab Content -->
        <div class="p-8">
            <!-- General Tab -->
            <div id="general" class="tab-content">
                <form action="{{ route('settings.update') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    
                    <!-- Profile Section -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">اطلاعات پروفایل</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">نام کامل</label>
                                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ایمیل</label>
                                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">شماره تلفن</label>
                                <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" placeholder="اختیاری">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">عکس پروفایل</label>
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="avatar" class="w-20 h-20 rounded-full object-cover border mb-2">
                                @endif
                                <input type="file" name="avatar" accept="image/*" class="w-full">
                                @error('avatar')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300">
                            <i class="fas fa-save ml-2"></i>ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Tab -->
            <div id="security" class="tab-content hidden">
                <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">تغییر رمز عبور</h3>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">رمز عبور فعلی</label>
                        <input type="password" name="current_password" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">رمز عبور جدید</label>
                            <input type="password" name="password" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">تایید رمز عبور</label>
                            <input type="password" name="password_confirmation" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300">
                            <i class="fas fa-key ml-2"></i>تغییر رمز عبور
                        </button>
                    </div>
                </form>
            </div>


            <!-- Privacy Tab -->
            <div id="privacy" class="tab-content hidden">
                <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">حریم خصوصی و امنیت</h3>

                    @include('partials.security-settings')

                    <!-- Session Management -->
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 transition-colors duration-300">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">مدیریت جلسات</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">از تمام دستگاه ها خارج شوید</p>
                        <form action="{{ route('logout-all-devices') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white font-semibold rounded-lg hover:bg-red-700 dark:hover:bg-red-600 transition-colors duration-300 text-sm">
                                خروج از همه دستگاه ها
                            </button>
                        </form>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300">
                            <i class="fas fa-save ml-2"></i>ذخیره تنظیمات
                        </button>
                    </div>
                </form>
            </div>

            <!-- KYC Tab -->
            <div id="kyc" class="tab-content hidden">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">احراز هویت (KYC)</h3>

                <div class="space-y-4">
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" disabled @if(auth()->user()->kyc_verified) checked @endif class="w-5 h-5 text-indigo-600 rounded">
                            <span class="font-semibold text-gray-800 dark:text-gray-100">احراز هویت KYC تایید شده</span>
                        </label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">برای ارسال مدارک، از فرم زیر استفاده کنید. پس از ارسال، لطفاً از طریق پنل مدیریت یا phpMyAdmin وضعیت را تایید/رد کنید.</p>
                    </div>

                    <form id="kyc-form" action="{{ route('kyc.upload') }}" method="POST" enctype="multipart/form-data" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">آپلود مدارک (پاسپورت، کارت ملی، مدارک دیگر)</label>
                            <input type="file" name="documents[]" multiple accept="image/*,application/pdf" class="w-full">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">گرفتن عکس توسط دوربین (سلفی)</label>

                            <div class="space-y-2">
                                <button type="button" id="start-camera" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded">روشن کردن دوربین</button>
                                <video id="video" autoplay class="w-64 h-48 bg-black rounded hidden"></video>
                                <button type="button" id="capture-btn" class="px-4 py-2 bg-indigo-600 text-white rounded hidden">عکس بگیر</button>
                                <canvas id="canvas" class="hidden"></canvas>
                                <img id="selfie-preview" class="w-40 h-40 object-cover rounded hidden" alt="Selfie preview">
                                <input type="hidden" name="selfie_data" id="selfie_data">
                                <div class="pt-2">
                                    <input type="file" name="selfie" accept="image/*" class="w-full">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">ارسال مدارک</button>
                        </div>
                    </form>

                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">فایل‌های آپلود شده</h4>
                        @if(!empty(auth()->user()->kyc_selfie))
                            <div class="mb-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">سلفی:</p>
                                <a href="{{ route('kyc.selfie') }}" target="_blank" class="text-indigo-600">مشاهده سلفی</a>
                            </div>
                        @endif

                        @if(!empty(auth()->user()->kyc_documents))
                            <ul class="list-disc list-inside">
                                @foreach(auth()->user()->kyc_documents as $doc)
                                    <li><a href="{{ route('kyc.document', ['filename' => basename($doc)]) }}" target="_blank" class="text-indigo-600">{{ basename($doc) }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-gray-600 dark:text-gray-400">هیچ پرونده‌ای آپلود نشده است.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // Remove active styling from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'text-gray-800', 'dark:text-gray-100');
        btn.classList.add('border-transparent', 'text-gray-600', 'dark:text-gray-400');
    });
    
    // Show selected tab
    document.getElementById(tab).classList.remove('hidden');
    
    // Add active styling to clicked button
    const btn = document.getElementById('tab-' + tab);
    btn.classList.remove('border-transparent', 'text-gray-600', 'dark:text-gray-400');
    btn.classList.add('border-indigo-600', 'text-gray-800', 'dark:text-gray-100');
}

// Camera and KYC helpers
(function(){
    function setupKycCamera() {
        const startBtn = document.getElementById('start-camera');
        const video = document.getElementById('video');
        const captureBtn = document.getElementById('capture-btn');
        const canvas = document.getElementById('canvas');
        const selfiePreview = document.getElementById('selfie-preview');
        const selfieData = document.getElementById('selfie_data');

        if (!startBtn) return;

        let stream;
        startBtn.addEventListener('click', async function() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.classList.remove('hidden');
                captureBtn.classList.remove('hidden');
                startBtn.classList.add('hidden');
            } catch (e) {
                alert('دسترسی به دوربین امکان‌پذیر نیست: ' + e.message);
            }
        });

        captureBtn.addEventListener('click', function() {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            const dataUrl = canvas.toDataURL('image/jpeg');
            selfieData.value = dataUrl;
            selfiePreview.src = dataUrl;
            selfiePreview.classList.remove('hidden');
            // stop stream
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
            }
            video.classList.add('hidden');
            captureBtn.classList.add('hidden');
            startBtn.classList.remove('hidden');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupKycCamera();

        // If a hash is present in the URL (e.g. #security), open that tab
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById(hash)) {
            switchTab(hash);
        }
    });
})();
</script>

@endsection
