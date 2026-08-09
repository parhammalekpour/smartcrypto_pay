@extends('layouts.dashboard')

@section('title', __('merchant_settings.title'))
@section('page-title', __('merchant_settings.title'))
@section('page-subtitle', __('merchant_settings.page_subtitle'))

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
                <i class="fas fa-store ml-2"></i>{{ __('merchant_settings.store_info_tab') }}
            </button>
            <button onclick="switchTab('business')" id="tab-business" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-briefcase ml-2"></i>{{ __('merchant_settings.business_info_tab') }}
            </button>
            <button onclick="switchTab('contact')" id="tab-contact" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-phone ml-2"></i>{{ __('merchant_settings.contact_info_tab') }}
            </button>
            <button onclick="switchTab('security')" id="tab-security" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-lock ml-2"></i>{{ __('merchant_settings.security_tab') }}
            </button>
            <button onclick="switchTab('kyc')" id="tab-kyc" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition min-w-fit">
                <i class="fas fa-id-card ml-2"></i>{{ __('merchant_settings.kyc_tab') }}
            </button>
        </div>

        <!-- Tab Content -->
        <div class="p-8">
            <form action="{{ route('merchant.settings.update') }}" method="POST" class="space-y-6" id="settings-form" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                
                <!-- General Tab -->
                <div id="general" class="tab-content">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">{{ __('merchant_settings.account_store_info') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.full_name') }}</label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.account_email') }}</label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>

                        <!-- Phone -->

                        <!-- Avatar -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.store_profile_photo') }}</label>
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
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.personal_phone') }}</label>
                            <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="{{ __('merchant_settings.optional') }}"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Shop Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.store_name') }}</label>
                            <input type="text" name="shop_name" value="{{ old('shop_name', auth()->user()->shop_name) }}" placeholder="{{ __('merchant_settings.store_business_name') }}"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>
                    </div>

                    <!-- Shop Description -->
                    <div class="pt-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.store_description') }}</label>
                        <textarea name="shop_description" rows="4" placeholder="{{ __('merchant_settings.store_description_placeholder') }}"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">{{ old('shop_description', auth()->user()->shop_description) }}</textarea>
                    </div>
                </div>

                <!-- Business Tab -->
                <div id="business" class="tab-content hidden">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">{{ __('merchant_settings.business_info_title') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Business Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.business_email') }}</label>
                            <input type="email" name="business_email" value="{{ old('business_email', auth()->user()->business_email) }}" placeholder="{{ __('merchant_settings.business_contact_email') }}"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Business Phone -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.business_phone') }}</label>
                            <input type="tel" name="business_phone" value="{{ old('business_phone', auth()->user()->business_phone) }}" placeholder="{{ __('merchant_settings.contact_number') }}"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Website URL -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.website') }}</label>
                            <input type="url" name="website_url" value="{{ old('website_url', auth()->user()->website_url) }}" placeholder="https://example.com"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>

                        <!-- Business License -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.business_license') }}</label>
                            <input type="text" name="business_license" value="{{ old('business_license', auth()->user()->business_license) }}" placeholder="{{ __('merchant_settings.license_number') }}"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">
                        </div>
                    </div>

                    <!-- Business Address -->
                    <div class="pt-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.business_address') }}</label>
                        <textarea name="business_address" rows="3" placeholder="{{ __('merchant_settings.business_address_placeholder') }}"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300">{{ old('business_address', auth()->user()->business_address) }}</textarea>
                    </div>
                </div>

                <!-- Contact Tab -->
                <div id="contact" class="tab-content hidden">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">{{ __('merchant_settings.contact_summary') }}</h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('merchant_settings.person_name') }}</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ auth()->user()->name }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('merchant_settings.personal_email') }}</p>
                                <p class="text-gray-800 dark:text-gray-100 break-all">{{ auth()->user()->email }}</p>
                            </div>

                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('merchant_settings.personal_phone') }}</p>
                                <p class="text-gray-800 dark:text-gray-100">{{ auth()->user()->phone ?? __('merchant_settings.not_provided') }}</p>
                            </div>

                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('merchant_settings.business_email') }}</p>
                                <p class="text-gray-800 dark:text-gray-100">{{ auth()->user()->business_email ?? __('merchant_settings.not_provided') }}</p>
                            </div>

                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('merchant_settings.business_phone') }}</p>
                                <p class="text-gray-800 dark:text-gray-100">{{ auth()->user()->business_phone ?? __('merchant_settings.not_provided') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Form Actions (only for general, business, contact tabs) -->
            <div id="form-actions" class="flex gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" form="settings-form" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition flex items-center gap-2">
                    <i class="fas fa-save"></i>{{ __('merchant_settings.save_changes') }}
                </button>
                <button type="reset" form="settings-form" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    {{ __('merchant_settings.cancel') }}
                </button>
            </div>

            <!-- Security Tab -->
            <div id="security" class="tab-content hidden">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">{{ __('merchant_settings.account_security') }}</h3>

                @include('partials.security-settings')

                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">{{ __('merchant_settings.change_password') }}</h3>
                
                <!-- Password Change Form -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                    <div class="flex gap-3">
                        <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 mt-1"></i>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ __('merchant_settings.password_change_info') }}</p>
                    </div>
                </div>

                <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.current_password') }}</label>
                        <input type="password" name="current_password" 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        @error('current_password')
                            <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.new_password') }}</label>
                            <input type="password" name="password" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                            @error('password')
                                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.confirm_password') }}</label>
                            <input type="password" name="password_confirmation" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">{{ __('merchant_settings.password_requirements') }}</p>
                        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                            <li><i class="fas fa-check-circle ml-2"></i>{{ __('merchant_settings.password_requirement_8') }}</li>
                            <li><i class="fas fa-check-circle ml-2"></i>{{ __('merchant_settings.password_requirement_upper') }}</li>
                            <li><i class="fas fa-check-circle ml-2"></i>{{ __('merchant_settings.password_requirement_lower') }}</li>
                            <li><i class="fas fa-check-circle ml-2"></i>{{ __('merchant_settings.password_requirement_number') }}</li>
                        </ul>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300 flex items-center gap-2">
                            <i class="fas fa-key"></i>{{ __('merchant_settings.change_password') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- KYC Tab -->
                <div id="kyc" class="tab-content hidden mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">{{ __('merchant_settings.kyc_tab') }}</h3>

                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 mb-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" disabled @if(auth()->user()->kyc_verified) checked @endif class="w-5 h-5 text-indigo-600 rounded">
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ __('merchant_settings.kyc_verified') }}</span>
                        </label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ __('merchant_settings.kyc_submission_note') }}</p>
                    </div>

                    <form id="kyc-form" action="{{ route('kyc.upload') }}" method="POST" enctype="multipart/form-data" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.upload_documents_label') }}</label>
                                                    <div class="flex gap-4 items-start">
                                                        <input id="kycDocumentsInput" type="file" name="documents[]" multiple accept="image/*,application/pdf" class="w-full" />
                                                        <div id="kycDocumentsPreview" class="grid grid-cols-3 gap-2"></div>
                                                    </div>
                                                </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('merchant_settings.selfie_label') }}</label>

                            <div class="space-y-2">
                                <button type="button" id="start-camera" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded">{{ __('merchant_settings.enable_camera') }}</button>
                                <video id="video" autoplay class="w-64 h-48 bg-black rounded hidden"></video>
                                <button type="button" id="capture-btn" class="px-4 py-2 bg-indigo-600 text-white rounded hidden">{{ __('merchant_settings.take_photo') }}</button>
                                <canvas id="canvas" class="hidden"></canvas>
                                <img id="selfie-preview" class="w-40 h-40 object-cover rounded hidden" alt="Selfie preview">
                                <input type="hidden" name="selfie_data" id="selfie_data">
                                <div class="pt-2">
                                                                    <input id="kycSelfieInput" type="file" name="selfie" accept="image/*" class="w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">{{ __('merchant_settings.submit_documents') }}</button>
                        </div>
                    </form>

                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 mt-4">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">{{ __('merchant_settings.uploaded_files') }}</h4>
                        @if(!empty(auth()->user()->kyc_selfie))
                            <div class="mb-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('merchant_settings.selfie') }}:</p>
                                <a href="{{ route('kyc.selfie') }}" target="_blank" class="text-indigo-600">{{ __('merchant_settings.view_selfie') }}</a>
                            </div>
                        @endif

                        @if(!empty(auth()->user()->kyc_documents))
                            <ul class="list-disc list-inside">
                                @foreach(auth()->user()->kyc_documents as $doc)
                                    <li><a href="{{ route('kyc.document', ['filename' => basename($doc)]) }}" target="_blank" class="text-indigo-600">{{ basename($doc) }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('merchant_settings.no_files_uploaded') }}</p>
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
                    alert('{{ __('merchant_settings.camera_access_error') }}' + e.message);
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

