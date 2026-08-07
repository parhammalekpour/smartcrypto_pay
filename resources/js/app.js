import './bootstrap';
import './auto-refresh';
import WalletBalance from './wallet-balance';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Initialize wallet balance sync module
WalletBalance.init();
