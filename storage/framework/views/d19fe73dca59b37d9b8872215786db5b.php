

<?php $__env->startSection('title', __('wallets.page_title') . ' - CryptoPay'); ?>
<?php $__env->startSection('page-title', __('wallets.page_title')); ?>
<?php $__env->startSection('page-subtitle', __('wallets.page_subtitle')); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<style>
    .wallet-shell {
        padding: 0.25rem 0;
    }
    .wallet-card {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
    .wallet-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.10);
    }
    .wallet-action-btn {
        min-height: 46px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 600;
        padding: 0.75rem 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
    }
    .wallet-action-btn:hover {
        transform: translateY(-1px);
    }
    .wallet-address-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        justify-content: space-between;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 0.7rem 0.8rem;
    }
    .wallet-address-pill code {
        background: transparent;
        padding: 0;
        font-size: 0.82rem;
        color: #334155;
        font-family: ui-monospace, SFMono-Regular, SFMono-Regular, Menlo, monospace;
    }
    .wallet-asset-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        padding: 0.35rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
</style>
<?php $__env->stopPush(); ?>

<div class="wallet-shell space-y-6">
    <div class="grid grid-cols-1 xl:grid-cols-[1.6fr_minmax(280px,320px)] gap-6">
        <div class="rounded-[24px] border border-slate-200 bg-slate-950 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm text-slate-400"><?php echo e(__('wallets.live_prices_subtitle')); ?></p>
                    <h3 class="mt-1 text-xl font-semibold text-white">BTC / ETH</h3>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-500/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-indigo-200">
                    <i class="fas fa-chart-line text-xs"></i>
                    Binance
                </span>
            </div>

            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Bitcoin</p>
                            <p class="mt-2 text-2xl font-semibold text-white" data-live-btc-price>$0.00</p>
                        </div>
                        <span class="wallet-asset-tag bg-white/10 text-slate-300">BTC</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-400" data-live-btc-change><?php echo e(__('wallets.no_change')); ?></p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Ethereum</p>
                            <p class="mt-2 text-2xl font-semibold text-white" data-live-eth-price>$0.00</p>
                        </div>
                        <span class="wallet-asset-tag bg-white/10 text-slate-300">ETH</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-400" data-live-eth-change><?php echo e(__('wallets.no_change')); ?></p>
                </div>
            </div>

            <p class="mt-4 text-xs text-slate-500" data-live-price-updated><?php echo e(__('wallets.last_updated')); ?> -</p>
        </div>

        <div class="flex flex-col justify-between rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500"><?php echo e(__('wallets.wallet_control')); ?></p>
                        <h3 class="text-lg font-semibold text-slate-900"><?php echo e(__('wallets.add_wallet')); ?></h3>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <p class="text-sm leading-6 text-slate-600"><?php echo e(__('wallets.add_wallet_description')); ?></p>
            </div>
            <button onclick="openAddWalletModal()" class="mt-6 inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <i class="fas fa-plus"></i>
                <?php echo e(__('wallets.add_wallet')); ?>

            </button>
        </div>
    </div>

    <?php if($wallets && $wallets->count() > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wallet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="wallet-card p-6 transition">
                    <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl
                                <?php if($wallet->currency === 'BTC'): ?> bg-orange-100
                                <?php elseif($wallet->currency === 'ETH'): ?> bg-slate-100
                                <?php else: ?> bg-teal-100 <?php endif; ?>">
                                <?php if($wallet->currency === 'BTC'): ?>
                                    <i class="fab fa-bitcoin text-orange-600 text-lg"></i>
                                <?php elseif($wallet->currency === 'ETH'): ?>
                                    <i class="fab fa-ethereum text-slate-700 text-lg"></i>
                                <?php else: ?>
                                    <i class="fas fa-coins text-teal-600 text-lg"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-slate-900"><?php echo e($wallet->currency); ?></p>
                                <p class="text-sm text-slate-500"><?php echo e($wallet->currency === 'BTC' ? 'Bitcoin' : ($wallet->currency === 'ETH' ? 'Ethereum' : 'Tether')); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"><?php echo e(__('wallets.balance')); ?></p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900"><?php echo e(\App\Support\NumberHelper::formatCryptoAmount($wallet->balance)); ?> <span class="text-lg font-medium text-slate-500"><?php echo e($wallet->currency); ?></span></p>
                        <p class="mt-2 text-sm text-slate-500">
                            <span class="font-medium text-slate-400"><?php echo e(__('wallets.estimated_value')); ?>:</span>
                            <span class="ml-1 font-semibold text-slate-700 usd-price" data-currency="<?php echo e($wallet->currency); ?>" data-balance="<?php echo e($wallet->balance); ?>">≈ $0.00</span>
                        </p>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"><?php echo e(__('wallets.address')); ?></p>
                        <div class="wallet-address-pill mt-2">
                            <code><?php echo e(substr($wallet->wallet_address, 0, 6)); ?>...<?php echo e(substr($wallet->wallet_address, -6)); ?></code>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white transition hover:bg-indigo-700" onclick="copyToClipboard('<?php echo e($wallet->wallet_address); ?>')" aria-label="<?php echo e(__('wallets.copy')); ?>">
                                <i class="fas fa-copy text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-2">
                        <button type="button" class="wallet-action-btn bg-indigo-50 text-indigo-700 hover:bg-indigo-100" onclick="copyToClipboard('<?php echo e($wallet->wallet_address); ?>')">
                            <i class="fas fa-copy"></i>
                            <?php echo e(__('wallets.copy')); ?>

                        </button>
                        <a href="<?php echo e(route('user.send', ['sender_wallet_id' => $wallet->id])); ?>" class="wallet-action-btn bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
                            <i class="fas fa-paper-plane"></i>
                            <?php echo e(__('wallets.send')); ?>

                        </a>

                        <?php if(floatval($wallet->balance) > 0): ?>
                            <a href="<?php echo e(route('tickets.create', ['subject' => __('wallets.delete_ticket_subject', ['currency' => $wallet->currency]), 'message' => __('wallets.delete_ticket_message', ['address' => $wallet->wallet_address, 'balance' => $wallet->balance, 'currency' => $wallet->currency])])); ?>" onclick="alert('<?php echo e(__('wallets.delete_wallet_balance_alert')); ?>');" class="wallet-action-btn bg-rose-50 text-rose-700 hover:bg-rose-100" title="<?php echo e(__('wallets.delete_wallet_ticket_title')); ?>">
                                <i class="fas fa-trash"></i>
                                <?php echo e(__('common.delete')); ?>

                            </a>
                        <?php else: ?>
                            <form method="POST" action="<?php echo e(route('user.wallets.destroy', $wallet)); ?>" style="display:contents;" onsubmit="return confirm('<?php echo e(__('wallets.delete_wallet_confirm')); ?>');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="wallet-action-btn bg-rose-50 text-rose-700 hover:bg-rose-100">
                                    <i class="fas fa-trash"></i>
                                    <?php echo e(__('common.delete')); ?>

                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="rounded-[24px] border border-slate-200 bg-white p-12 text-center shadow-sm">
            <i class="fas fa-wallet text-5xl text-slate-300 mb-4"></i>
            <p class="text-slate-600 mb-4"><?php echo e(__('wallets.no_wallets')); ?></p>
            <button onclick="openAddWalletModal()" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                <?php echo e(__('wallets.create_first_wallet')); ?>

            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Add Wallet Modal -->
<div id="addWalletModal" class="hidden fixed inset-0 bg-black/50 px-4 py-6 flex items-center justify-center z-50">
    <div class="w-full max-w-md rounded-[24px] border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900"><?php echo e(__('wallets.create_new_wallet')); ?></h3>
                <p class="mt-1 text-sm text-slate-500"><?php echo e(__('wallets.add_wallet_description')); ?></p>
            </div>
            <button type="button" onclick="closeAddWalletModal()" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="<?php echo e(route('user.wallets.store')); ?>" class="mt-5 space-y-4">
            <?php echo csrf_field(); ?>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700"><?php echo e(__('wallets.select_currency')); ?></label>
                <select name="currency" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value=""><?php echo e(__('wallets.select_option')); ?></option>
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                    <option value="USDT">Tether (USDT)</option>
                </select>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 rounded-2xl bg-indigo-600 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <?php echo e(__('common.create')); ?>

                </button>
                <button type="button" onclick="closeAddWalletModal()" class="flex-1 rounded-2xl bg-slate-100 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    <?php echo e(__('common.cancel')); ?>

                </button>
            </div>
        </form>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    let cryptoPrices = { btc: 0, eth: 0, usd: 1 };
    let previousPrices = { btc: null, eth: null };

    function formatPrice(price) {
        return '$' + price.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatChange(current, previous) {
        if (previous === null || previous === 0) {
            return '<?php echo e(__('wallets.no_change')); ?>';
        }

        const diff = current - previous;
        const pct = previous === 0 ? 0 : (diff / previous) * 100;
        const arrow = diff > 0 ? '▲' : diff < 0 ? '▼' : '–';
        const sign = diff === 0 ? '–' : arrow;
        return `${sign} ${formatPrice(Math.abs(diff))} (${Math.abs(pct).toFixed(2)}%)`;
    }

    function updateLivePriceCards() {
        const btcPriceEl = document.querySelector('[data-live-btc-price]');
        const ethPriceEl = document.querySelector('[data-live-eth-price]');
        const btcChangeEl = document.querySelector('[data-live-btc-change]');
        const ethChangeEl = document.querySelector('[data-live-eth-change]');
        const updatedEl = document.querySelector('[data-live-price-updated]');

        if (btcPriceEl) {
            btcPriceEl.textContent = formatPrice(cryptoPrices.btc);
        }
        if (ethPriceEl) {
            ethPriceEl.textContent = formatPrice(cryptoPrices.eth);
        }
        if (btcChangeEl) {
            btcChangeEl.textContent = formatChange(cryptoPrices.btc, previousPrices.btc);
            btcChangeEl.classList.toggle('text-emerald-400', cryptoPrices.btc > (previousPrices.btc || 0));
            btcChangeEl.classList.toggle('text-rose-400', cryptoPrices.btc < (previousPrices.btc || 0));
        }
        if (ethChangeEl) {
            ethChangeEl.textContent = formatChange(cryptoPrices.eth, previousPrices.eth);
            ethChangeEl.classList.toggle('text-emerald-400', cryptoPrices.eth > (previousPrices.eth || 0));
            ethChangeEl.classList.toggle('text-rose-400', cryptoPrices.eth < (previousPrices.eth || 0));
        }
        if (updatedEl) {
            updatedEl.textContent = '<?php echo e(__('wallets.last_updated')); ?> ' + new Date().toLocaleTimeString('en-US');
        }
    }

    function updateWalletPrices() {
        const priceElements = document.querySelectorAll('.usd-price');
        priceElements.forEach(element => {
            const currency = element.getAttribute('data-currency');
            const balance = parseFloat(element.getAttribute('data-balance')) || 0;
            let price = 1;

            if (currency === 'BTC') {
                price = cryptoPrices.btc;
            } else if (currency === 'ETH') {
                price = cryptoPrices.eth;
            } else if (currency === 'USDT' || currency === 'USD') {
                price = 1;
            }

            const usdValue = balance * price;
            element.textContent = '≈ ' + formatPrice(usdValue);
        });
    }

    async function fetchAndDisplayPrices() {
        try {
            const response = await fetch('<?php echo e(route("public.api.crypto-prices")); ?>' + '?t=' + Date.now(), { cache: 'no-store' });
            const data = await response.json();

            previousPrices.btc = cryptoPrices.btc || previousPrices.btc;
            previousPrices.eth = cryptoPrices.eth || previousPrices.eth;

            cryptoPrices.btc = parseFloat(data.btc) || 0;
            cryptoPrices.eth = parseFloat(data.eth) || 0;

            updateLivePriceCards();
            updateWalletPrices();
        } catch (error) {
            console.error('Failed to fetch crypto prices:', error);
        }
    }

    function openAddWalletModal() {
        document.getElementById('addWalletModal').classList.remove('hidden');
    }
    function closeAddWalletModal() {
        document.getElementById('addWalletModal').classList.add('hidden');
    }
    
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('<?php echo e(__('wallets.address_copied')); ?>');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }

    // Fetch prices on page load
    fetchAndDisplayPrices();

    // Fetch prices every 5 seconds for a live-updating feel
    setInterval(fetchAndDisplayPrices, 5000);

</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\smart-cryptopay\resources\views/user/wallets.blade.php ENDPATH**/ ?>