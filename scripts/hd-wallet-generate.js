import { Wallet } from "ethers";

// Simple script to generate an HD wallet (mnemonic-based) using ethers
// Usage: node scripts/hd-wallet-generate.js <currency>
// Prints JSON to stdout: { address, privateKey }

const args = process.argv.slice(2);
const currency = args[0] || 'ETH';

try {
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
