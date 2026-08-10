<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('landing');
        }

        return redirect()->route(
            auth()->user()->isMerchant() ? 'documentation.type' : 'documentation.type',
            ['type' => auth()->user()->isMerchant() ? 'merchant' : 'user']
        );
    }

    public function show(Request $request, ?string $type = null, ?string $category = null, ?string $article = null)
    {
        if (!auth()->check()) {
            return redirect()->route('landing');
        }

        $type = $type ?? (auth()->user()->isMerchant() ? 'merchant' : 'user');
        $type = strtolower($type);

        if (!in_array($type, ['user', 'merchant'], true)) {
            abort(404);
        }

        if ($type === 'merchant' && !auth()->user()->isMerchant()) {
            abort(403);
        }

        if ($type === 'user' && !auth()->user()->isUser() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $locale = app()->getLocale();
        $catalog = $this->catalog($type, $locale);
        $currentCategory = $category && isset($catalog['categories'][$category]) ? $catalog['categories'][$category] : null;
        $currentArticle = null;

        if ($category && $article && $currentCategory) {
            foreach ($currentCategory['articles'] as $candidate) {
                if ($candidate['slug'] === $article) {
                    $currentArticle = $candidate;
                    break;
                }
            }
        }

        if ($article && !$currentArticle) {
            abort(404);
        }

        $allArticles = [];
        foreach ($catalog['categories'] as $categorySlug => $categoryData) {
            foreach ($categoryData['articles'] as $articleData) {
                $allArticles[] = [
                    'type' => $type,
                    'category' => $categorySlug,
                    'article' => $articleData['slug'],
                    'title' => $articleData['title'],
                    'summary' => $articleData['summary'],
                ];
            }
        }

        $currentIndex = null;
        if ($currentArticle) {
            foreach ($allArticles as $index => $item) {
                if ($item['type'] === $type && $item['category'] === $category && $item['article'] === $article) {
                    $currentIndex = $index;
                    break;
                }
            }
        }

        $prevArticle = $currentIndex !== null && isset($allArticles[$currentIndex - 1]) ? $allArticles[$currentIndex - 1] : null;
        $nextArticle = $currentIndex !== null && isset($allArticles[$currentIndex + 1]) ? $allArticles[$currentIndex + 1] : null;

        return view('documentation.index', [
            'type' => $type,
            'catalog' => $catalog,
            'currentCategory' => $currentCategory,
            'currentArticle' => $currentArticle,
            'prevArticle' => $prevArticle,
            'nextArticle' => $nextArticle,
            'allArticles' => $allArticles,
            'breadcrumbs' => $this->buildBreadcrumbs($type, $category, $article, $catalog),
        ]);
    }

    private function catalog(string $type, string $locale): array
    {
        if ($type === 'merchant') {
            return $this->merchantCatalog($locale);
        }

        return $this->userCatalog($locale);
    }

    private function merchantCatalog(string $locale): array
    {
        $c = [
            'title' => $this->t($locale, 'Documentation Merchant', 'Merchant Documentation'),
            'description' => $this->t($locale, 'مرجع Merchant برای آشنایی با داشبورد، Store، Wallet، درگاه پرداخت و تراکنش‌ها.', 'Merchant help center for dashboard, store setup, wallets, payments, and transactions.'),
            'categories' => []
        ];

        $c['categories']['getting-started'] = [
            'slug' => 'getting-started',
            'title' => $this->t($locale, 'شروع کار', 'Getting Started'),
            'description' => $this->t($locale, 'ورود به حرفه Merchant، داشبورد و ساختار اصلی پنل.', 'Merchant entry, dashboard and merchant panel structure.'),
            'topics' => [$this->t($locale, 'Merchant چیست؟', 'What is a Merchant?'), $this->t($locale, 'داشبورد Merchant', 'Merchant dashboard'), $this->t($locale, 'ساختار پنل Merchant', 'Merchant panel structure')],
            'articles' => [
                $this->article('what-is-merchant', $this->t($locale, 'Merchant چیست؟', 'What is a Merchant?'), $this->t($locale, 'Merchant حسابی برای دریافت پرداخت از مشتری است.', 'A Merchant account receives payments from customers.'), [[ 'type' => 'paragraph', 'text' => $this->t($locale, 'Merchant در SmartCryptoPay یک کسب‌وکار یا فروشگاه است که می‌تواند Store، Walletها، Payment Request و Transaction History را مدیریت کند.', 'A Merchant is a business store account that can manage Store settings, wallets, payment requests and transaction history.')], [ 'type' => 'heading', 'text' => $this->t($locale, 'امکانات Merchant', 'Merchant capabilities')], [ 'type' => 'list', 'title' => $this->t($locale, 'امکانات اصلی', 'Main capabilities'), 'items' => [$this->t($locale, 'ساخت Store', 'Create a store'), $this->t($locale, 'ایجاد Wallet', 'Create a wallet'), $this->t($locale, 'ساخت Payment Request', 'Create a payment request'), $this->t($locale, 'بررسی پرداخت‌ها', 'Review payments'), $this->t($locale, 'پیگیری تراکنش‌ها', 'Track transactions')]], ['type' => 'steps', 'title' => $this->t($locale, 'روال پیشنهادی', 'Suggested workflow'), 'items' => [$this->t($locale, 'به /merchant بروید.', 'Open /merchant.'), $this->t($locale, 'Store را در /merchant/settings تکمیل کنید.', 'Configure store settings in /merchant/settings.'), $this->t($locale, 'Wallet موردنظر را از /merchant/wallets بسازید.', 'Create a wallet from /merchant/wallets.'), $this->t($locale, 'Payment Request را از /merchant/payments تعریف کنید.', 'Create a payment request from /merchant/payments.')]]]),
                $this->article('merchant-dashboard', $this->t($locale, 'داشبورد Merchant', 'Merchant dashboard'), $this->t($locale, 'داشبورد Merchant خلاصه وضعیت فروشگاه و حساب شما را نشان می‌دهد.', 'The Merchant dashboard summarises account health and business payment activity.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'داشبورد Merchant شامل کارت‌های Wallet، Pending Payments، Total Revenue و وضعیت تراکنش‌ها است.', 'The Merchant dashboard shows wallet information, pending payments, settlement visibility, and transaction activity.')], ['type' => 'heading', 'text' => $this->t($locale, 'مسیرها', 'Entry points')], ['type' => 'steps', 'title' => $this->t($locale, 'ورود به مسیرهای اصلی', 'Main paths'), 'items' => [$this->t($locale, '/merchant', ' /merchant'), $this->t($locale, '/merchant/payments', '/merchant/payments'), $this->t($locale, '/merchant/transactions', '/merchant/transactions'), $this->t($locale, '/merchant/wallets', '/merchant/wallets')]]]),
                $this->article('merchant-panel-structure', $this->t($locale, 'ساختار پنل Merchant', 'Merchant panel structure'), $this->t($locale, 'پنل Merchant مجموعه مسیرهای اصلی برای فروشنده، پرداخت‌ها، کیف پول‌ها و تنظیمات فروشگاه را هماهنگ می‌کند.', 'The Merchant panel provides a guided workspace for settlement, payments, wallet lifecycle, store configuration and reporting.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'پنل Merchant نقش مرکز عملیاتی فروشنده را دارد و در آن فروشگاه، پرداخت‌های دریافتی، تراکنش‌های blockchain و تنظیمات حساب ترکیب می‌شوند.', 'The Merchant panel acts as the operational console for a business account and combines store configuration, incoming payments, blockchain transactions and account setup.')], ['type' => 'heading', 'text' => $this->t($locale, 'بخش‌های اصلی', 'Main areas')], ['type' => 'list', 'title' => $this->t($locale, 'امکانات پنل', 'Panel capabilities'), 'items' => [$this->t($locale, 'داشبورد فروشنده و خلاصه درآمد', 'Merchant dashboard and revenue summary'), $this->t($locale, 'ایجاد و پیگیری Payment Request', 'Create and review payment requests'), $this->t($locale, 'مدیریت Walletها و آدرس‌های دریافت', 'Wallet and receiving address management'), $this->t($locale, 'لیست تراکنش‌ها و وضعیت‌ها', 'Transaction and status monitoring'), $this->t($locale, 'تنظیمات Store و Business', 'Store and business settings')]], ['type' => 'steps', 'title' => $this->t($locale, 'روال پیشنهادی', 'Suggested merchant flow'), 'items' => [$this->t($locale, 'از /merchant به داشبورد وارد شوید.', 'Open /merchant.'), $this->t($locale, 'Store خود را در /merchant/settings تکمیل کنید.', 'Complete the store profile at /merchant/settings.'), $this->t($locale, 'از /merchant/wallets Walletی بسازید یا باز کنید.', 'Create or review merchant wallets from /merchant/wallets.'), $this->t($locale, 'Payment Request را از /merchant/payments ثبت کنید.', 'Create a payment request from /merchant/payments.'), $this->t($locale, 'وضعیت پرداخت‌ها را از /merchant/transactions بررسی کنید.', 'Review payment status from /merchant/transactions.')]]]),
            ],
        ];

        $c['categories']['store'] = [
            'slug' => 'store',
            'title' => $this->t($locale, 'Store', 'Store'),
            'description' => $this->t($locale, 'Store Merchant و تنظیمات Business.', 'Store and Business setup.'),
            'topics' => [$this->t($locale, 'ایجاد Store', 'Create Store'), $this->t($locale, 'تنظیمات Store', 'Store settings'), $this->t($locale, 'اطلاعات Business', 'Business information')],
            'articles' => [
                $this->article('merchant-store', $this->t($locale, 'ایجاد و تنظیم Store', 'Create and configure Store'), $this->t($locale, 'از مسیر /merchant/settings Store و Business را ثبت کنید.', 'Complete store or business details in /merchant/settings.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'برای پرداخت‌های مشتری، اطلاعات Store و Business برای یک Payment Request باید آماده باشد.', 'A valid store profile is required when you want to issue a payment request.')], ['type' => 'steps', 'title' => $this->t($locale, 'مراحل', 'Steps'), 'items' => [$this->t($locale, 'به /merchant/settings بروید.', 'Open /merchant/settings.'), $this->t($locale, 'نام فروشگاه، توضیحات و اطلاعات تماس را وارد کنید.', 'Fill store name, description and contact data.'), $this->t($locale, 'اطلاعات را ذخیره کنید.', 'Save the form.')]]]),
            ],
        ];

        $c['categories']['payment'] = [
            'slug' => 'payment',
            'title' => $this->t($locale, 'Payment', 'Payment'),
            'description' => $this->t($locale, 'Payment Request و بررسی پرداخت مشتری.', 'Payment request creation and customer payment monitoring.'),
            'topics' => [$this->t($locale, 'Payment Request', 'Payment Request'), $this->t($locale, 'Payment Status', 'Payment Status'), $this->t($locale, 'Transaction Hash', 'Transaction Hash')],
            'articles' => [
                $this->article('merchant-payment-request', $this->t($locale, 'Payment Request Merchant', 'Merchant payment request'), $this->t($locale, 'Payment Request میزان سفارش و Currency دقیق را برای مشتری مشخص می‌کند.', 'A payment request contains invoice number, amount, currency, and recipient context.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'Merchant از /merchant/payments یک درخواست پرداخت می‌سازد. پس از پرداخت، وضعیت تغییر به Paid می‌کند.', 'Merchant opens /merchant/payments, creates a request and then reviews the payment status.')], ['type' => 'heading', 'text' => $this->t($locale, 'چکیده وضعیت', 'Status overview')], ['type' => 'list', 'title' => $this->t($locale, 'وضعیت‌ها', 'Statuses'), 'items' => [$this->t($locale, 'Pending', 'Pending'), $this->t($locale, 'Paid', 'Paid'), $this->t($locale, 'Cancelled', 'Cancelled')]]]),
            ],
        ];

        $c['categories']['wallets'] = [
            'slug' => 'wallets',
            'title' => $this->t($locale, 'Wallets', 'Wallets'),
            'description' => $this->t($locale, 'Wallet Management Merchant و آدرس دریافت.', 'Merchant wallet management and receiving addresses.'),
            'topics' => [$this->t($locale, 'Wallet Management', 'Wallet Management'), $this->t($locale, 'مشاهده Walletها', 'List wallets'), $this->t($locale, 'دریافت ارز', 'Receive funds')],
            'articles' => [
                $this->article('merchant-wallets-article', $this->t($locale, 'Walletهای Merchant', 'Merchant wallets'), $this->t($locale, 'از /merchant/wallets Walletهای ETH/BTC/USDT را ببینید و آدرس‌ها را کپی کنید.', 'Open /merchant/wallets to review BTC, ETH, or USDT accounts and receive addresses.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'هر Wallet به Currency خود وصل است و آدرس دریافت آن در صفحه نمایش داده می‌شود.', 'Each wallet record is tied to a currency and shows a receiving address.')], ['type' => 'steps', 'title' => $this->t($locale, 'روال', 'Workflow'), 'items' => [$this->t($locale, 'Wallet را ایجاد یا باز کنید.', 'Create or open the wallet.'), $this->t($locale, 'Address را Copy کنید.', 'Copy the wallet address.'), $this->t($locale, 'موجودی را از صفحه بررسی کنید.', 'Review the balance.')]]]),
            ],
        ];

        $c['categories']['transactions'] = [
            'slug' => 'transactions',
            'title' => $this->t($locale, 'Transactions', 'Transactions'),
            'description' => $this->t($locale, 'لیست تراکنش‌های Merchant و بررسی آدرس‌ها.', 'Merchant transaction review and confirmation inspection.'),
            'topics' => [$this->t($locale, 'مشاهده تراکنش‌ها', 'View transactions'), $this->t($locale, 'وضعیت تراکنش‌ها', 'Transaction statuses'), $this->t($locale, 'Transaction Hash', 'Transaction Hash')],
            'articles' => [
                $this->article('merchant-transactions-article', $this->t($locale, 'تراکنش‌های Merchant', 'Merchant transactions'), $this->t($locale, 'در مسیر /merchant/transactions آرگومان‌های تراکنش و وضعیت blockchain قابل بررسی است.', 'The merchant transaction screen lists wallet and payment request activity.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'تراکنش‌ها شامل Amount، Currency، Status، Addressها، Confirmations و Hash هستند.', 'Transactions expose amount, currency, status, addresses, confirmations and hash values.')], ['type' => 'list', 'title' => $this->t($locale, 'وضعیت‌ها', 'Statuses'), 'items' => [$this->t($locale, 'Pending', 'Pending'), $this->t($locale, 'Confirmed', 'Confirmed'), $this->t($locale, 'Failed', 'Failed')]]]),
            ],
        ];

        $c['categories']['security'] = [
            'slug' => 'security',
            'title' => $this->t($locale, 'Security & Account', 'Security & Account'),
            'description' => $this->t($locale, 'امنیت حساب Merchant.', 'Security and account safety.'),
            'topics' => [$this->t($locale, 'Account Security', 'Account Security'), $this->t($locale, 'Email Verification', 'Email Verification'), $this->t($locale, '2FA', '2FA')],
            'articles' => [
                $this->article('merchant-security-article', $this->t($locale, 'امنیت حساب Merchant', 'Merchant account security'), $this->t($locale, 'برای حفظ حساب Merchant، 2FA و Email Verification مهم هستند.', 'Email verification and 2FA are the primary security controls.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'صفحه /merchant/settings محل مدیریت الزامات امنیتی است.', 'The /merchant/settings view is the central place to review security settings.')], ['type' => 'callout', 'variant' => 'warning', 'title' => $this->t($locale, 'هشدار', 'Warning'), 'text' => $this->t($locale, 'هیچ‌وقت داده‌های حساس حساب و Private Key را منتشر نکنید.', 'Never share password, private keys, or account specific data.')]]),
            ],
        ];

        return $c;
    }

    private function userCatalog(string $locale): array
    {
        $c = [
            'title' => $this->t($locale, 'Documentation کاربر', 'User Documentation'),
            'description' => $this->t($locale, 'مرجع کاربر برای سنجش Dashboard، Wallet، Transactions و Settings.', 'User help center for dashboard, wallets, transactions, settings and account security.'),
            'categories' => []
        ];

        $c['categories']['getting-started'] = [
            'slug' => 'getting-started',
            'title' => $this->t($locale, 'شروع کار', 'Getting Started'),
            'description' => $this->t($locale, 'خروجی مرتب و اولین مسیرهای کاربر.', 'First steps in SmartCryptoPay.'),
            'topics' => [$this->t($locale, 'SmartCryptoPay چیست؟', 'What is SmartCryptoPay?'), $this->t($locale, 'Dashboard چیست؟', 'What is the dashboard?'), $this->t($locale, 'ساختار پنل User', 'User panel structure')],
            'articles' => [
                $this->article('smart-cryptopay-user', $this->t($locale, 'SmartCryptoPay چیست؟', 'What is SmartCryptoPay?'), $this->t($locale, 'SmartCryptoPay یک پنل پرداخت ارزهای دیجیتال برای حساب کاربری و Walletها است.', 'SmartCryptoPay is the digital asset payment workflow for users and wallet balancing.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'کاربر در SmartCryptoPay می‌تواند Walletها، تراکنش‌ها و تنظیمات حساب را ببینید.', 'Users can open dashboards, wallet pages, transaction pages and settings pages.')], ['type' => 'steps', 'title' => $this->t($locale, 'روال ورود', 'Login flow'), 'items' => [$this->t($locale, 'ثبت‌نام یا ورود را انجام دهید.', 'Create an account or sign in.'), $this->t($locale, 'Email خود را تایید کنید.', 'Verify the email address.'), $this->t($locale, 'به /user بروید.', 'Open /user.')]]]),
                $this->article('user-dashboard-user', $this->t($locale, 'داشبورد کاربر', 'User dashboard'), $this->t($locale, 'داشبورد کاربر مسیر اصلی نمایش شرایط حساب و Walletها است.', 'The dashboard is the first screen showing wallet health and account activity.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'داشبورد کاربر به Walletها، Pending payments، تراکنش‌های اخیر و جداول خلاصه لینک می‌دهد.', 'The user dashboard lists wallet summary, payment requests and recent transactions.')], ['type' => 'heading', 'text' => $this->t($locale, 'ورود به داشبورد', 'Access')], ['type' => 'steps', 'title' => $this->t($locale, 'آغاز کار', 'Start'), 'items' => [$this->t($locale, 'به /user بروید.', 'Open /user.'), $this->t($locale, 'Walletهای شما نمایش داده می‌شود.', 'Review wallets.'), $this->t($locale, 'تراکنش‌ها را از /user/transactions ببینید.', 'Open /user/transactions.')]]]),
                $this->article('user-panel-structure', $this->t($locale, 'ساختار پنل User', 'User panel structure'), $this->t($locale, 'پنل کاربر مسیر اصلی تجربه احراز هویت، Wallet، تراکنش و تنظیمات حساب است.', 'The User panel is the main account workspace and central interface for wallet operations, transfers, transaction activity and account preferences.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'پنل کاربر شامل داشبورد دارایی، لیست Walletها، تراکنش‌ها و مسیرهای پرداخت الکترونیک است.', 'The user panel covers account health, wallet records, transaction monitoring and online payment activity.')], ['type' => 'heading', 'text' => $this->t($locale, 'بخش‌های اصلی', 'Main areas')], ['type' => 'list', 'title' => $this->t($locale, 'امکانات پنل', 'Panel capabilities'), 'items' => [$this->t($locale, 'داشبورد برای مشاهده موجودی و وضعیت حساب', 'Asset dashboard and account health summary'), $this->t($locale, 'مدیریت Walletها و آدرس‌های دریافت', 'Wallet management and receive addresses'), $this->t($locale, 'ارسال/انتقال ارز و بررسی وضعیت تراکنش‌ها', 'Send and monitor asset movement'), $this->t($locale, 'تاریخچه تراکنش‌ها با فیلتر و جستجو', 'Transaction history with search and filtering'), $this->t($locale, 'تنظیمات پروفایل، اعلان‌ها و امنیت', 'Profile, notification and security settings')]], ['type' => 'steps', 'title' => $this->t($locale, 'روال مرسوم', 'Common user flow'), 'items' => [$this->t($locale, 'ثبت‌نام یا ورود انجام دهید.', 'Register or sign in.'), $this->t($locale, 'به /user/ بروید و داشبورد را باز کنید.', 'Open /user and review the dashboard.'), $this->t($locale, 'از /user/wallets Wallet ایجاد یا باز کنید.', 'Create or open a wallet through /user/wallets.'), $this->t($locale, 'Transaction History را از /user/transactions بررسی کنید.', 'Review transaction history from /user/transactions.'), $this->t($locale, 'تنظیمات را در /user/settings نگاه دارید.', 'Review account settings in /user/settings.')]]]),
            ],
        ];

        $c['categories']['account'] = [
            'slug' => 'account',
            'title' => $this->t($locale, 'Account', 'Account'),
            'description' => $this->t($locale, 'ثبت‌نام، ورود، Email Verification، امنیت و 2FA.', 'Registration, login, email verification, security and 2FA.'),
            'topics' => [$this->t($locale, 'ثبت‌نام', 'Register'), $this->t($locale, 'ورود', 'Login'), $this->t($locale, 'Email Verification', 'Email verification'), $this->t($locale, '2FA', '2FA')],
            'articles' => [
                $this->article('user-account-user', $this->t($locale, 'ثبت‌نام و ورود', 'Sign-up and sign-in'), $this->t($locale, 'کاربر می‌تواند حساب خود را بسازد و با Email خود به سیستم وارد شود.', 'The user can create a SmartCryptoPay account and sign in.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'ثبت‌نام و ورود از مسیر Auth در داشبورد اصلی انجام می‌شود. کاربر باید Email خود را Verify کند.', 'Registration and login use the authentication endpoints. Email verification is part of account validation.')], ['type' => 'steps', 'title' => $this->t($locale, 'باز کردن مسیر', 'Workflow'), 'items' => [$this->t($locale, 'ثبت‌نام را انجام دهید.', 'Register.'), $this->t($locale, 'Email را تایید کنید.', 'Verify your email.'), $this->t($locale, 'به داشبورد وارد شوید.', 'Open the user dashboard.')]]]),
            ],
        ];

        $c['categories']['wallets'] = [
            'slug' => 'wallets',
            'title' => $this->t($locale, 'Wallets', 'Wallets'),
            'description' => $this->t($locale, 'Walletهای کاربر و آدرس‌های دریافت.', 'Wallet records, balance and receiving address.'),
            'topics' => [$this->t($locale, 'Wallet چیست؟', 'Wallet'), $this->t($locale, 'ETH Wallet', 'ETH wallet'), $this->t($locale, 'BTC Wallet', 'BTC wallet'), $this->t($locale, 'USDT Wallet', 'USDT wallet')],
            'articles' => [
                $this->article('user-wallet-management', $this->t($locale, 'Wallet کاربر', 'User wallets'), $this->t($locale, 'در /user/wallets می‌توانید Walletها و آدرس‌های دریافت ارز را کنترل کنید.', 'Users open /user/wallets to create and review wallet records.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'هر Wallet ارز خاص خودش را دارد، مانند ETH، BTC یا USDT. آدرس دریافت برای هر Wallet قابل مشاهده است.', 'Each wallet belongs to a single currency and exposes a receiving address.')], ['type' => 'steps', 'title' => $this->t($locale, 'ساخت Wallet', 'Create wallet'), 'items' => [$this->t($locale, 'به /user/wallets بروید.', 'Open /user/wallets.'), $this->t($locale, 'Currency مطمئن را انتخاب و Wallet بسازید.', 'Select currency and create wallet.'), $this->t($locale, 'Address را کپی کنید.', 'Copy address.')]]]),
            ],
        ];

        $c['categories']['transactions'] = [
            'slug' => 'transactions',
            'title' => $this->t($locale, 'Transactions', 'Transactions'),
            'description' => $this->t($locale, 'تاریخچه Incoming و Outgoing تراکنش‌ها و وضعیت‌ها.', 'Transaction history, status, hash and address visibility.'),
            'topics' => [$this->t($locale, 'Transaction History', 'Transaction history'), $this->t($locale, 'Incoming Transaction', 'Incoming transaction'), $this->t($locale, 'Outgoing Transaction', 'Outgoing transaction')],
            'articles' => [
                $this->article('user-transactions', $this->t($locale, 'Transaction History کاربر', 'User transaction history'), $this->t($locale, 'لیست تراکنش‌های کاربر در /user/transactions قرار دارد.', 'The history page lists all wallet activity.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'در این بخش تراکنش‌ها با نوع، Status، Amount و Addressها نمایش داده می‌شوند.', 'Users can inspect transaction type, status, amounts and relevant blockchain addresses.')], ['type' => 'list', 'title' => $this->t($locale, 'وضعیت‌ها', 'Statuses'), 'items' => [$this->t($locale, 'Pending', 'Pending'), $this->t($locale, 'Confirmed', 'Confirmed'), $this->t($locale, 'Failed', 'Failed')]]]),
            ],
        ];

        $c['categories']['security'] = [
            'slug' => 'security',
            'title' => $this->t($locale, 'Security', 'Security'),
            'description' => $this->t($locale, 'نکات امنیتی Wallet و ارسال ارز.', 'Wallet, account, and transfer safety.'),
            'topics' => [$this->t($locale, 'امنیت Wallet', 'Wallet security'), $this->t($locale, 'Private Key', 'Private Key'), $this->t($locale, 'عدم اشتراک اطلاعات حساس', 'Do not share sensitive data')],
            'articles' => [
                $this->article('user-wallet-security', $this->t($locale, 'امنیت Wallet', 'Wallet security'), $this->t($locale, 'برای جلوگیری از دسترسی غیرمجاز، Wallet و مهم‌تر از آن Private Key را مراقبت کنید.', 'Wallets should be used with care and protected through account security.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'Private Key یا آدرس حساب را با کسی اشتراک نگذارید.', 'Never share your private key or secure information.')], ['type' => 'callout', 'variant' => 'warning', 'title' => $this->t($locale, 'هشدار', 'Warning'), 'text' => $this->t($locale, 'در مسیر Send یا Transfer، آدرس مقصد را با دقت بررسی کنید.', 'Before sending a transfer, verify destination address.')]]),
            ],
        ];

        $c['categories']['profile-and-settings'] = [
            'slug' => 'profile-and-settings',
            'title' => $this->t($locale, 'Profile & Settings', 'Profile & Settings'),
            'description' => $this->t($locale, 'پروفایل، اعلان‌ها، Language و تنظیمات حساب کاربر.', 'Profile, notifications, language, and account preferences.'),
            'topics' => [$this->t($locale, 'Profile', 'Profile'), $this->t($locale, 'Notifications', 'Notifications'), $this->t($locale, 'Language', 'Language')],
            'articles' => [
                $this->article('profile-settings-user', $this->t($locale, 'تنظیمات Profile', 'Profile settings'), $this->t($locale, 'در /user/settings می‌توانید اطلاعات حساب را بروزرسانی کنید.', 'The settings page lets users manage personal information and account display preferences.'), [['type' => 'paragraph', 'text' => $this->t($locale, 'کاربر می‌تواند Email، نام، پسورد، اعلان‌ها و تنظیمات نمایش را در این صفحه بررسی کند.', 'Users can review email, name, password, notification preferences, and theme visibility.')], ['type' => 'steps', 'title' => $this->t($locale, 'تنظیمات', 'Settings flow'), 'items' => [$this->t($locale, 'به /user/settings بروید.', 'Open /user/settings.'), $this->t($locale, 'پروفایل را ویرایش کنید.', 'Edit profile.'), $this->t($locale, 'اعلان‌ها و امنیت را بررسی کنید.', 'Review notifications and security.')]]]),
            ],
        ];

        return $c;
    }

    private function article(string $slug, string $title, string $summary, array $content): array
    {
        return ['slug' => $slug, 'title' => $title, 'summary' => $summary, 'content' => $content];
    }

    private function buildBreadcrumbs(string $type, ?string $category, ?string $article, array $catalog): array
    {
        $base = $type === 'merchant'
            ? $this->t(app()->getLocale(), 'Documentation Merchant', 'Merchant Documentation')
            : $this->t(app()->getLocale(), 'Documentation کاربر', 'User Documentation');

        $breadcrumbs = [[ 'label' => $base, 'url' => route('documentation.type', ['type' => $type]) ]];

        if ($category && isset($catalog['categories'][$category])) {
            $breadcrumbs[] = [
                'label' => $catalog['categories'][$category]['title'],
                'url' => route('documentation.category', ['type' => $type, 'category' => $category]),
            ];
        }

        if ($article && $category && isset($catalog['categories'][$category])) {
            $articleFound = collect($catalog['categories'][$category]['articles'])->firstWhere('slug', $article);
            if ($articleFound) {
                $breadcrumbs[] = ['label' => $articleFound['title'], 'url' => null];
            }
        }

        return $breadcrumbs;
    }

    private function t(string $locale, string $fa, string $en): string
    {
        return $locale === 'fa' ? $fa : $en;
    }
}
