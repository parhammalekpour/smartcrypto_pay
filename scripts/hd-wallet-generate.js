import { Wallet, utils } from "ethers";

// Script to generate or validate wallets using ethers.js
// Usage:
//  - generate: node scripts/hd-wallet-generate.js generate <currency>
//  - validate: node scripts/hd-wallet-generate.js validate <currency> <address>
// If the first argument is omitted, 'generate' is assumed.

const args = process.argv.slice(2);
const action = (args[0] || 'generate').toLowerCase();

try {
    if (action === 'validate') {
        const currency = args[1] || 'ETH';
        const address = args[2] || '';
        const valid = utils.isAddress(address);
        console.log(JSON.stringify({ valid }));
        process.exit(0);
    }

    if (action === 'derive') {
        const currency = args[1] || 'ETH';
        const privateKey = args[2] || '';
        if (!privateKey || !/^0x[0-9a-fA-F]{64}$/.test(privateKey)) {
            console.error(JSON.stringify({ error: 'Invalid private key format' }));
            process.exit(1);
        }

        const wallet = new Wallet(privateKey);
        console.log(JSON.stringify({ address: wallet.address }));
        process.exit(0);
    }

    // Default: generate a new wallet
    const currency = args[0] || 'ETH';
    const wallet = Wallet.createRandom(); // creates mnemonic-backed wallet (HD)
    const out = {
        address: wallet.address,
        privateKey: wallet.privateKey
    };

    // Write JSON to stdout with no extra logging
    console.log(JSON.stringify(out));
} catch (err) {
    // Print error as JSON to stderr
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
}
