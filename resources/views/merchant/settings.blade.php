@extends('layouts.dashboard')

@section('title', 'تنظیمات فروشنده - CryptoPay')
@section('page-title', 'تنظیمات')
@section('page-subtitle', 'مدیریت تنظیمات حساب و اطلاعات فروشنده')

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
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex-wrap">
            <button onclick="switchTab('general')" id="tab-general" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-700 dark:text-gray-300 border-b-4 border-indigo-600 dark:border-indigo-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition active-tab min-w-fit">
                <i class="fas fa-store ml-2"></i>اطلاعات فروشگاه
            </button>
            <button onclick="switchTab('business')" id="tab-business" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-briefcase ml-2"></i>اطلاعات کسب‌وکار
            </button>
            <button onclick="switchTab('contact')" id="tab-contact" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-phone ml-2"></i>اطلاعات تماس
            </button>
            <button onclick="switchTab('security')" id="tab-security" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-lock ml-2"></i>امنیت
            </button>
            <button onclick="switchTab('kyc')" id="tab-kyc" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-id-card ml-2"></i>احراز هویت (KYC)
            </button>
        </div>

        <!-- Tab Content -->
        <div class="p-8">
            <form action="{{ route('merchant.settings.update') }}" method="POST" class="space-y-6" id="settings-form" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                
                <!-- General Tab -->
                <div id="general" class="tab-content">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">اطلاعات حساب و فروشگاه</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">نام کامل</label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ایمیل حساب</label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>

                        <!-- Phone -->

                        <!-- Avatar -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">عکس پروفایل فروشگاه</label>
                            <div class="flex items-center gap-4">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="merchant-avatar" class="w-20 h-20 rounded-full object-cover border">
                                @else
                                    <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center border"> 
                                        <i class="fas fa-store text-gray-400"></i>
                                    </div>
                                @endif

                                <input type="file" name="avatar" accept="image/*" class="mt-1 block">
                            </div>
                            @error('avatar')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">شماره تلفن شخصی</label>
                            <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="اختیاری"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Shop Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">نام فروشگاه</label>
                            <input type="text" name="shop_name" value="{{ old('shop_name', auth()->user()->shop_name) }}" placeholder="نام تجاری فروشگاه"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>
                    </div>

                    <!-- Shop Description -->
                    <div class="pt-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">توضیحات فروشگاه</label>
                        <textarea name="shop_description" rows="4" placeholder="توضیحات مختصری درباره فروشگاه و خدمات شما"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">{{ old('shop_description', auth()->user()->shop_description) }}</textarea>
                    </div>
                </div>

                <!-- Business Tab -->
                <div id="business" class="tab-content hidden">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">اطلاعات کسب‌وکار</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Business Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ایمیل کسب‌وکار</label>
                            <input type="email" name="business_email" value="{{ old('business_email', auth()->user()->business_email) }}" placeholder="ایمیل تماس کسب‌وکار"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Business Phone -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">شماره تلفن کسب‌وکار</label>
                            <input type="tel" name="business_phone" value="{{ old('business_phone', auth()->user()->business_phone) }}" placeholder="شماره تماس"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Website URL -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">وب‌سایت</label>
                            <input type="url" name="website_url" value="{{ old('website_url', auth()->user()->website_url) }}" placeholder="https://example.com"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Business License -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">شماره مجوز کسب‌وکار</label>
                            <input type="text" name="business_license" value="{{ old('business_license', auth()->user()->business_license) }}" placeholder="شماره مجوز"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>
                    </div>

                    <!-- Business Address -->
                    <div class="pt-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">آدرس کسب‌وکار</label>
                        <textarea name="business_address" rows="3" placeholder="آدرس محل کسب‌وکار"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">{{ old('business_address', auth()->user()->business_address) }}</textarea>
                    </div>
                </div>

                <!-- Contact Tab -->
                <div id="contact" class="tab-content hidden">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">خلاصه اطلاعات تماس</h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">نام شخص</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ auth()->user()->name }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ایمیل شخصی</p>
                                <p class="text-gray-800 dark:text-gray-100 break-all">{{ auth()->user()->email }}</p>
                            </div>

                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">تلفن شخصی</p>
                                <p class="text-gray-800 dark:text-gray-100">{{ auth()->user()->phone ?? 'ثبت نشده' }}</p>
                            </div>

                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ایمیل کسب‌وکار</p>
                                <p class="text-gray-800 dark:text-gray-100">{{ auth()->user()->business_email ?? 'ثبت نشده' }}</p>
                            </div>

                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">تلفن کسب‌وکار</p>
                                <p class="text-gray-800 dark:text-gray-100">{{ auth()->user()->business_phone ?? 'ثبت نشده' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Form Actions (only for general, business, contact tabs) -->
            <div id="form-actions" class="flex gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" form="settings-form" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition flex items-center gap-2">
                    <i class="fas fa-save"></i>ذخیره تغییرات
                </button>
                <button type="reset" form="settings-form" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    لغو
                </button>
            </div>

            <!-- Security Tab -->
            <div id="security" class="tab-content hidden">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">امنیت حساب</h3>

                @include('partials.security-settings')

                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">تغییر رمز عبور</h3>
                
                <!-- Password Change Form -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                    <div class="flex gap-3">
                        <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 mt-1"></i>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">برای تغییر رمز عبور، رمز عبور فعلی خود را وارد کنید و سپس رمز عبور جدید را انتخاب کنید.</p>
                    </div>
                </div>

                <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">رمز عبور فعلی</label>
                        <input type="password" name="current_password" 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        @error('current_password')
                            <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">رمز عبور جدید</label>
                            <input type="password" name="password" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                            @error('password')
                                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">تایید رمز عبور جدید</label>
                            <input type="password" name="password_confirmation" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">الزامات رمز عبور:</p>
                        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                            <li><i class="fas fa-check-circle ml-2"></i>حداقل 8 کاراکتر</li>
                            <li><i class="fas fa-check-circle ml-2"></i>حداقل یک حرف بزرگ</li>
                            <li><i class="fas fa-check-circle ml-2"></i>حداقل یک حرف کوچک</li>
                            <li><i class="fas fa-check-circle ml-2"></i>حداقل یک عدد</li>
                        </ul>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300 flex items-center gap-2">
                            <i class="fas fa-key"></i>تغییر رمز عبور
                        </button>
                    </div>
                </form>
            </div>

            <!-- KYC Tab -->
                <div id="kyc" class="tab-content hidden mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">احراز هویت (KYC)</h3>

                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 mb-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" disabled @if(auth()->user()->kyc_verified) checked @endif class="w-5 h-5 text-indigo-600 rounded">
                            <span class="font-semibold text-gray-800 dark:text-gray-100">احراز هویت KYC تایید شده</span>
                        </label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">در صورتی که KYC انجام نشده است، مدارک خود را آپلود کنید.</p>
                    </div>

                    <form id="kyc-form" action="{{ route('kyc.upload') }}" method="POST" enctype="multipart/form-data" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">آپلود مدارک</label>
                                                    <div class="flex gap-4 items-start">
                                                        <input id="kycDocumentsInput" type="file" name="documents[]" multiple accept="image/*,application/pdf" class="w-full" />
                                                        <div id="kycDocumentsPreview" class="grid grid-cols-3 gap-2"></div>
                                                    </div>
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
                                                                    <input id="kycSelfieInput" type="file" name="selfie" accept="image/*" class="w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">ارسال مدارک</button>
                        </div>
                    </form>

                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 mt-4">
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
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-b-4', 'border-indigo-600', 'dark:border-indigo-500', 'text-gray-700', 'dark:text-gray-300');
            btn.classList.add('border-b-4', 'border-transparent', 'text-gray-600', 'dark:text-gray-400');
        });
        
        // Show selected tab content
        const selectedTab = document.getElementById(tabName);
        if (selectedTab) {
            selectedTab.classList.remove('hidden');
        }
        
        // Add active class to clicked button
        const button = document.getElementById('tab-' + tabName);
        if (button) {
            button.classList.remove('border-transparent', 'text-gray-600', 'dark:text-gray-400');
            button.classList.add('border-indigo-600', 'dark:border-indigo-500', 'text-gray-700', 'dark:text-gray-300');
        }
        
        // Show/hide form actions based on tab (hide for security and kyc tabs)
        const formActions = document.getElementById('form-actions');
        if (tabName === 'security' || tabName === 'kyc') {
            formActions.classList.add('hidden');
        } else {
            formActions.classList.remove('hidden');
        }
    }

    // Camera handlers for KYC
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
                if (selfieData) selfieData.value = dataUrl;
                if (selfiePreview) {
                    selfiePreview.src = dataUrl;
                    selfiePreview.classList.remove('hidden');
                }
                // stop stream
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                }
                video.classList.add('hidden');
                captureBtn.classList.add('hidden');
                startBtn.classList.remove('hidden');
            });

        // Preview when selecting selfie file from disk
        const selfieInput = document.getElementById('kycSelfieInput');
        if (selfieInput) {
            selfieInput.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (file && file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    if (selfiePreview) {
                        selfiePreview.src = url;
                        selfiePreview.classList.remove('hidden');
                    }
                    // set selfie_data to empty so server uses file upload instead
                    if (selfieData) selfieData.value = '';
                    // revoke object URL after image loads
                    if (selfiePreview) selfiePreview.onload = () => URL.revokeObjectURL(url);
                } else {
                    if (selfiePreview) {
                        selfiePreview.src = '';
                        selfiePreview.classList.add('hidden');
                    }
                }
            });
        }

        // Preview selected documents (multiple)
        const docsInput = document.getElementById('kycDocumentsInput');
        const docsPreview = document.getElementById('kycDocumentsPreview');
        let docObjectUrls = [];
        if (docsInput && docsPreview) {
            docsInput.addEventListener('change', function(){
                // clear existing previews and revoke URLs
                docsPreview.innerHTML = '';
                docObjectUrls.forEach(u => URL.revokeObjectURL(u));
                docObjectUrls = [];

                const files = Array.from(this.files || []);
                files.forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.className = 'w-full h-24 object-cover rounded';
                        const url = URL.createObjectURL(file);
                        docObjectUrls.push(url);
                        img.src = url;
                        const wrapper = document.createElement('div');
                        wrapper.appendChild(img);
                        docsPreview.appendChild(wrapper);
                        img.onload = () => URL.revokeObjectURL(url);
                    } else if (file.type === 'application/pdf') {
                        const link = document.createElement('a');
                        link.textContent = file.name;
                        link.href = '#';
                        link.className = 'text-sm text-indigo-600';
                        const wrapper = document.createElement('div');
                        wrapper.appendChild(link);
                        docsPreview.appendChild(wrapper);
                    } else {
                        const p = document.createElement('p');
                        p.textContent = file.name;
                        const wrapper = document.createElement('div');
                        wrapper.appendChild(p);
                        docsPreview.appendChild(wrapper);
                    }
                });
            });
        }
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

