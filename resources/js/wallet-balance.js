// Reusable Wallet Balance module
// - Finds wallet cards on the page by looking for known anchors/forms produced by existing Blade views
// - Fetches balances via GET /api/wallet/{id}/balance
// - Updates only the balance text inside each card
// - Shows a small loading skeleton while fetching
// - Subscribes to Laravel Echo private channels if available for real-time updates

import axios from 'axios';

const WalletBalance = (function() {
    // Configuration
    const POLL_INTERVAL = 10000; // 10 seconds
    const COUNT_UP_DURATION = 600; // ms
    const SKELETON_HTML = '<span class="inline-block w-40 h-8 bg-gray-200 dark:bg-gray-700 animate-pulse rounded">&nbsp;</span>';

    // Internal state
    const wallets = new Map(); // walletId => { cardEl, balanceEl, usdEl, lastValue }
    let pollTimer = null;

    function findWalletCards() {
        const candidates = [];

        document.querySelectorAll('[data-wallet-id]').forEach(card => {
            if (card.dataset.walletId) {
                candidates.push(card);
            }
        });

        // Fallback for older cards without the explicit attribute.
        document.querySelectorAll('a[href*="sender_wallet_id="]').forEach(a => {
            const card = a.closest('div');
            if (card) candidates.push(card);
        });

        document.querySelectorAll('form[action*="/user/wallets/"]').forEach(f => {
            const card = f.closest('div');
            if (card) candidates.push(card);
        });

        document.querySelectorAll('p.text-3xl').forEach(p => {
            const card = p.closest('div');
            if (card && card.querySelector('p.font-mono')) candidates.push(card);
        });

        return Array.from(new Set(candidates));
    }

    function extractWalletIdFromCard(card) {
        if (card && card.dataset && card.dataset.walletId) {
            return String(card.dataset.walletId);
        }

        const a = card.querySelector('a[href*="sender_wallet_id="]');
        if (a) {
            try {
                const url = new URL(a.href, window.location.origin);
                const id = url.searchParams.get('sender_wallet_id');
                if (id) return id;
            } catch (e) {
                const m = a.getAttribute('href').match(/sender_wallet_id=(\d+)/);
                if (m) return m[1];
            }
        }

        const deleteForm = card.querySelector('form[action*="/user/wallets/"]');
        if (deleteForm) {
            const action = deleteForm.getAttribute('action');
            const m = action.match(/\/user\/wallets\/(\d+)/);
            if (m) return m[1];
        }

        const linkWithId = card.querySelector('a[href*="/wallet/"]');
        if (linkWithId) {
            const action = linkWithId.getAttribute('href');
            const m = action.match(/(\d+)/g);
            if (m) return m[m.length-1];
        }

        return null;
    }

    function findBalanceElement(card) {
        let el = card.querySelector('.wallet-balance-value');
        if (el) return el;

        el = card.querySelector('p.text-3xl');
        if (el) return el;

        // Secondary: first bold/large number inside card
        el = card.querySelector('p.font-bold, span.font-bold, h3');
        if (el) return el;

        // Fallback: any paragraph containing digits and a dot
        const paragraphs = card.querySelectorAll('p');
        for (const p of paragraphs) {
            if (/\d+([.,]\d+)?/.test(p.textContent)) return p;
        }

        return null;
    }

    function formatBalanceRaw(value) {
        const num = parseFloat(value ?? 0);
        if (!isFinite(num)) return '0';

        const fixed = num.toFixed(8).replace(/\.0+$/, '').replace(/(\.\d*?[1-9])0+$/, '$1');
        return fixed === '' ? '0' : fixed;
    }

    function setSkeleton(el) {
        if (!el) return;
        el._previousText = el.innerHTML;
        el.innerHTML = SKELETON_HTML;
    }

    function clearSkeleton(el) {
        if (!el) return;
        if (el._previousText !== undefined) {
            el.innerHTML = el._previousText;
            delete el._previousText;
        }
    }

    function animateChange(el, from, to) {
        if (!el) return;
        const fromNum = parseFloat(from) || 0;
        const toNum = parseFloat(to) || 0;
        const start = performance.now();
        el.style.transition = 'opacity 200ms ease';
        el.style.opacity = '0.5';

        function step(now) {
            const t = Math.min(1, (now - start) / COUNT_UP_DURATION);
            const cur = fromNum + (toNum - fromNum) * t;
            el.textContent = formatBalanceRaw(cur);
            if (t < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = formatBalanceRaw(toNum);
                el.style.opacity = '1';
            }
        }

        requestAnimationFrame(step);
    }

    async function fetchAndUpdateWallet(walletId, meta) {
        const { balanceEl } = meta;
        if (!walletId || !balanceEl) return;

        const version = (meta.latestIssuedVersion ?? 0) + 1;
        meta.latestIssuedVersion = version;

        // Show skeleton
        setSkeleton(balanceEl);

        try {
            const resp = await axios.get(`/api/wallet/${walletId}/balance`, { timeout: 8000 });
            const data = resp.data || {};
            const confirmedBalance = data.confirmed_balance ?? data.confirmed ?? data.wallet_balance ?? data.balance ?? data.available ?? '0';
            const newBal = formatBalanceRaw(confirmedBalance);

            // Ignore stale responses from older requests.
            if (version !== meta.latestIssuedVersion) {
                return;
            }

            const prev = meta.lastValue ?? (balanceEl.dataset?.initialValue ?? balanceEl.textContent.trim());

            // Update displayed USD price data attribute if present
            const usdEl = meta.usdEl;
            if (usdEl && confirmedBalance !== undefined) {
                usdEl.setAttribute('data-balance', confirmedBalance);
            }

            // Animate number change only if different
            if (Number(prev) !== Number(newBal)) {
                animateChange(balanceEl, parseFloat(prev || 0), parseFloat(newBal));
            } else {
                balanceEl.textContent = newBal;
            }

            // Keep lastValue
            meta.lastValue = newBal;

            // Add a small transient green badge 'Synced' by using title attribute (no DOM insertion)
            balanceEl.setAttribute('data-synced', 'true');
            balanceEl.setAttribute('title', 'Synced ' + new Date().toLocaleTimeString());

        } catch (err) {
            // Ignore stale request failures; newer update already wins.
            if (version !== meta.latestIssuedVersion) {
                return;
            }

            // On error, restore previous value and annotate
            const prev = meta.lastValue ?? (balanceEl.dataset?.initialValue ?? balanceEl.textContent.trim());
            balanceEl.textContent = prev || '0';
            balanceEl.setAttribute('title', 'Unable to refresh balance');
            console.error('Failed to fetch wallet balance for', walletId, err?.message || err);
        } finally {
            // Ensure skeleton cleared only for the newest active update.
            if (version === meta.latestIssuedVersion) {
                clearSkeleton(balanceEl);
            }
        }
    }

    function schedulePolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(() => {
            wallets.forEach((meta, walletId) => {
                // Avoid duplicate calls if a recent Echo update arrived very recently (within 2s)
                const now = Date.now();
                if (meta.lastEchoAt && (now - meta.lastEchoAt) < 2000) return;
                fetchAndUpdateWallet(walletId, meta);
            });
        }, POLL_INTERVAL);
    }

    function setupEcho() {
        if (!window.Echo) return;
        wallets.forEach((meta, walletId) => {
            try {
                const channel = window.Echo.private(`wallet.${walletId}`);
                channel.listen('WalletBalanceUpdated', (e) => {
                    console.log('Echo balance update:', e);
                    const version = (meta.latestIssuedVersion ?? 0) + 1;
                    meta.latestIssuedVersion = version;

                    // Prefer the confirmed on-chain balance for the displayed wallet value.
                    const payloadBalance = e.confirmed ?? e.confirmed_balance ?? e.balance ?? e.wallet_balance ?? e.balances?.confirmed ?? e.balances?.balance ?? null;
                    if (payloadBalance !== null && meta.balanceEl) {
                        const newBal = formatBalanceRaw(payloadBalance);
                        const prev = meta.lastValue ?? (meta.balanceEl.dataset?.initialValue ?? meta.balanceEl.textContent.trim());

                        if (Number(prev) !== Number(newBal)) {
                            animateChange(meta.balanceEl, parseFloat(prev || 0), parseFloat(newBal));
                        } else {
                            meta.balanceEl.textContent = newBal;
                        }
                        meta.lastValue = newBal;
                        meta.lastEchoAt = Date.now();
                        // Update usdEl data-balance if present and payload includes confirmed
                        if (meta.usdEl && (e.confirmed !== undefined || e.confirmed_balance !== undefined)) {
                            const confirmed = e.confirmed ?? e.confirmed_balance ?? null;
                            if (confirmed !== null) meta.usdEl.setAttribute('data-balance', confirmed);
                        }
                    }
                });
            } catch (err) {
                console.warn('Echo subscription failed for wallet.' + walletId, err);
            }
        });
    }

    function init() {
        document.addEventListener('DOMContentLoaded', () => {
            const cards = findWalletCards();
            cards.forEach(card => {
                const walletId = extractWalletIdFromCard(card);
                const balanceEl = findBalanceElement(card);
                const usdEl = card.querySelector('.usd-price');

                if (!walletId || !balanceEl) return;

                // Store initial value
                if (!balanceEl.dataset.initialValue) {
                    balanceEl.dataset.initialValue = (balanceEl.textContent || '').trim() || '0';
                }

                wallets.set(walletId, { cardEl: card, balanceEl, usdEl, lastValue: balanceEl.dataset.initialValue, lastEchoAt: null, latestIssuedVersion: 0 });
            });

            // Initial fetch for all wallets
            wallets.forEach((meta, id) => {
                // Defer slightly to allow UI to stabilize
                setTimeout(() => fetchAndUpdateWallet(id, meta), 150);
            });

            // Setup polling
            schedulePolling();

            // Setup Echo realtime updates
            setupEcho();
        });
    }

    return { init };
})();

export default WalletBalance;