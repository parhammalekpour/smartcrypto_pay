import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import https from 'https';
import dns from 'dns';
import { ethers } from 'ethers';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const loadDotEnv = () => {
    const candidates = [
        path.resolve(process.cwd(), '.env'),
        path.resolve(process.cwd(), '..', '.env'),
        path.resolve(__dirname, '..', '.env'),
    ];

    for (const candidate of candidates) {
        if (!candidate || !fs.existsSync(candidate)) {
            continue;
        }

        const contents = fs.readFileSync(candidate, 'utf8');
        const lines = contents.split(/\r?\n/);
        for (const line of lines) {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('#') || trimmed.startsWith('//')) {
                continue;
            }

            const match = trimmed.match(/^export\s+([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/);
            const keyValue = match ? match.slice(1) : trimmed.match(/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/);
            if (!keyValue) {
                continue;
            }

            const [, rawKey, rawValue] = keyValue;
            const key = rawKey.trim();
            let value = rawValue.trim();

            if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
                value = value.slice(1, -1);
            }

            if (process.env[key] === undefined || process.env[key] === '') {
                process.env[key] = value;
            }
        }
    }
};

loadDotEnv();

// Node helper for simple read-only Ethereum actions via Alchemy
// Usage:
//   node scripts/ethereum-client.js isAddress <address>
//   node scripts/ethereum-client.js balance <address>
//   node scripts/ethereum-client.js history <address> [limit] [fromBlock] [network]
//   node scripts/ethereum-client.js tokenTransfers <contract> <toAddress> [limit] [fromBlock] [network]
//   node scripts/ethereum-client.js receipt <txHash>
//   node scripts/ethereum-client.js blockNumber

const EXPECTED_NETWORK_NAME = 'sepolia';
const EXPECTED_CHAIN_ID = 11155111;
const RETRYABLE_RPC_ERROR_CODES = new Set(['EAI_FAIL', 'ECONNRESET', 'ETIMEDOUT', 'ECONNREFUSED', 'SERVER_ERROR']);
const RETRYABLE_RPC_ERROR_MESSAGES = [/EAI_FAIL/i, /ECONNRESET/i, /ETIMEDOUT/i, /ECONNREFUSED/i, /SERVER_ERROR/i, /ENOTFOUND/i, /EAI_AGAIN/i];
const RPC_ATTEMPTS = 3;

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

const shouldRetryError = (error) => {
    if (!error) {
        return false;
    }

    const code = error.code ? String(error.code) : '';
    const message = error.message ? String(error.message) : '';

    if (RETRYABLE_RPC_ERROR_CODES.has(code)) {
        return true;
    }

    const isFatalProviderError = /could not detect network|invalid rpc url|malformed provider|network mismatch|wrong network|provider cannot detect network|missing .*api key|chain mismatch/i.test(message);
    if (isFatalProviderError) {
        return false;
    }

    return RETRYABLE_RPC_ERROR_MESSAGES.some((pattern) => pattern.test(message));
};

const httpsProbeHost = async (rpcHost) => {
    return new Promise((resolve, reject) => {
        const req = https.request({
            hostname: rpcHost,
            method: 'HEAD',
            path: '/',
            timeout: 8000,
            headers: { Host: rpcHost }
        }, (res) => {
            res.resume();
            resolve(true);
        });

        req.on('error', reject);
        req.on('timeout', () => {
            req.destroy(new Error('HTTPS probe timeout'));
        });
        req.end();
    });
};

const probeDns = async (rpcHost) => {
    const diagnostics = {
        dnsLookupSuccess: false,
        resolvedAddress: null,
        dnsLookupErrorCode: null,
        dnsLookupErrorMessage: null,
        fallbackResolverUsed: false,
        httpsProbeSuccess: false,
        httpsProbeErrorMessage: null,
    };

    try {
        const { address } = await dns.promises.lookup(rpcHost, { family: 4 });
        diagnostics.dnsLookupSuccess = true;
        diagnostics.resolvedAddress = address;
        return diagnostics;
    } catch (e) {
        diagnostics.dnsLookupErrorCode = e?.code || null;
        diagnostics.dnsLookupErrorMessage = e?.message || String(e);
    }

    try {
        const resolver = new dns.promises.Resolver();
        const addresses = await resolver.resolve4(rpcHost);
        if (Array.isArray(addresses) && addresses.length > 0) {
            diagnostics.dnsLookupSuccess = true;
            diagnostics.resolvedAddress = addresses[0];
            diagnostics.fallbackResolverUsed = true;
            return diagnostics;
        }
    } catch (e) {
        diagnostics.dnsLookupErrorCode = diagnostics.dnsLookupErrorCode || e?.code || null;
        diagnostics.dnsLookupErrorMessage = diagnostics.dnsLookupErrorMessage || (e?.message || String(e));
    }

    try {
        await httpsProbeHost(rpcHost);
        diagnostics.httpsProbeSuccess = true;
    } catch (e) {
        diagnostics.httpsProbeErrorMessage = e?.message || String(e);
    }

    return diagnostics;
};

const retryRpcOperation = async (operation, label) => {
    let lastError = null;

    for (let attempt = 1; attempt <= RPC_ATTEMPTS; attempt += 1) {
        try {
            return await operation();
        } catch (error) {
            lastError = error;
            // If error is not considered retryable, throw immediately
            if (!shouldRetryError(error)) {
                throw error;
            }
            if (attempt === RPC_ATTEMPTS) {
                throw error;
            }
            // progressive backoff
            await wait(500 * attempt);
        }
    }

    throw lastError;
};

const rpcHostForNetwork = (network) => {
    if (network === 'sepolia') {
        return 'eth-sepolia.g.alchemy.com';
    }

    return `eth-${network}.g.alchemy.com`;
};

const normalizeRpcUrl = (candidate) => {
    if (!candidate || typeof candidate !== 'string') {
        throw new Error('Missing Ethereum RPC URL');
    }

    const trimmed = candidate.trim();
    if (trimmed === '') {
        throw new Error('Ethereum RPC URL is empty');
    }

    try {
        const parsed = new URL(trimmed);
        if (!parsed.protocol || !parsed.hostname) {
            throw new Error('RPC URL is malformed');
        }
        return trimmed;
    } catch (error) {
        throw new Error('Ethereum RPC URL is malformed: ' + trimmed);
    }
};

const normalizeHumanAmount = (raw) => {
    if (raw === null || raw === undefined) {
        return null;
    }

    let value = String(raw).trim();
    if (value === '') {
        return null;
    }

    value = value.replace(/\s+/g, '').replace(/\u00A0/g, '');

    if (/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)[eE][+-]?\d+$/.test(value)) {
        const numeric = Number(value);
        if (Number.isFinite(numeric)) {
            value = numeric.toLocaleString('en-US', {
                useGrouping: false,
                maximumFractionDigits: 18,
            });
        }
    }

    if (value.includes(',') && value.includes('.')) {
        const lastComma = value.lastIndexOf(',');
        const lastDot = value.lastIndexOf('.');
        if (lastComma > lastDot) {
            value = value.replace(/\./g, '').replace(/,/g, '.');
        } else {
            value = value.replace(/,/g, '');
        }
    } else if (value.includes(',')) {
        value = value.replace(/,/g, '.');
    }

    if (!/^[+-]?\d+(?:\.\d+)?$/.test(value)) {
        return null;
    }

    if (value.startsWith('+')) {
        value = value.slice(1);
    }

    return value;
};

const resolveRpcUrl = (apiKey, network, rpcHost) => {
    const overrideUrl = process.env.ETHEREUM_RPC_URL;
    if (overrideUrl && typeof overrideUrl === 'string' && overrideUrl.trim() !== '') {
        return normalizeRpcUrl(overrideUrl);
    }

    if (rpcHost && rpcHost.indexOf('http') === 0) {
        return normalizeRpcUrl(rpcHost);
    }

    return `https://${rpcHost}/v2/${apiKey}`;
};

const createProvider = (apiKey, network, rpcHost) => {
    const rpcUrl = resolveRpcUrl(apiKey, network, rpcHost);
    return new ethers.providers.JsonRpcProvider(rpcUrl, network);
};

const validateProviderNetwork = async (provider, network) => {
    const net = await retryRpcOperation(() => provider.getNetwork(), 'getNetwork');
    const actualChainId = Number(net?.chainId ?? 0);
    const actualNetwork = String(net?.name || '').toLowerCase();

    if (actualChainId !== EXPECTED_CHAIN_ID) {
        throw new Error(`Ethereum RPC chain mismatch: expected=${EXPECTED_CHAIN_ID} actual=${actualChainId}`);
    }

    if (actualNetwork && actualNetwork !== EXPECTED_NETWORK_NAME) {
        throw new Error(`Ethereum RPC network mismatch: expected=${EXPECTED_NETWORK_NAME} actual=${actualNetwork}`);
    }

    if (String(network).toLowerCase() !== EXPECTED_NETWORK_NAME) {
        throw new Error(`Ethereum RPC configuration mismatch: requested network=${network}, expected=${EXPECTED_NETWORK_NAME}`);
    }

    return net;
};

(async () => {
    try {
        const args = process.argv.slice(2);
        const action = args[0];

        const network = args.includes('--network') ? args[args.indexOf('--network') + 1] : (process.env.ETHEREUM_NETWORK || 'sepolia');

        // Allow parseEther to run without initializing RPC/provider — parseEther is a local utility
        if (action === 'parseEther') {
            const amount = normalizeHumanAmount(args[1]);
            if (!amount || Number.isNaN(Number(amount)) || !/^[0-9]+(\.[0-9]+)?$/.test(amount)) {
                console.error(JSON.stringify({ error: 'Invalid ETH amount' }));
                process.exit(2);
            }

            try {
                const wei = ethers.utils.parseEther(amount);
                console.log(JSON.stringify({ wei: wei.toString(), amount: amount }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: 'Invalid ETH amount' }));
                process.exit(2);
            }
        }

        // For all other actions require ALCHEMY_API_KEY and provider
        const apiKey = process.env.ALCHEMY_API_KEY;
        if (!apiKey) {
            console.error(JSON.stringify({ error: 'Missing ALCHEMY_API_KEY in environment' }));
            process.exit(2);
        }

        const rpcHost = rpcHostForNetwork(network);
        const provider = createProvider(apiKey, network, rpcHost);

        try {
            await validateProviderNetwork(provider, network);
        } catch (error) {
            console.error(JSON.stringify({
                error: {
                    message: error?.message || String(error),
                    code: error?.code || 'RPC_PROVIDER_VALIDATION_FAILED',
                },
                network,
                expectedChainId: EXPECTED_CHAIN_ID,
                rpcHost,
                rpcUrlHost: new URL(resolveRpcUrl(apiKey, network, rpcHost)).hostname,
            }));
            process.exit(2);
        }

        if (!action) {
            console.error(JSON.stringify({ error: 'No action provided' }));
            process.exit(2);
        }

        if (action === 'isAddress') {
            const address = args[1];
            if (!address) {
                console.log(JSON.stringify({ result: false }));
                process.exit(0);
            }
            const ok = ethers.utils.isAddress(address);
            console.log(JSON.stringify({ result: ok }));
            process.exit(0);
        }

        if (action === 'balance') {
            const address = args[1];
            if (!address) {
                console.error(JSON.stringify({ error: 'Address required' }));
                process.exit(2);
            }
            const balance = await provider.getBalance(address);
            const formatted = ethers.utils.formatEther(balance);
            console.log(JSON.stringify({ balance: formatted, wei: balance.toString() }));
            process.exit(0);
        }

        if (action === 'tokenBalance') {
            const contractAddr = args[1];
            const address = args[2];
            if (!contractAddr || !address) {
                console.error(JSON.stringify({ error: 'contractAddress and address required' }));
                process.exit(2);
            }

            const erc20Abi = [
                'function decimals() view returns (uint8)',
                'function balanceOf(address) view returns (uint256)'
            ];
            const contract = new ethers.Contract(contractAddr, erc20Abi, provider);

            let decimals = 18;
            try {
                decimals = await contract.decimals();
            } catch (e) {
                console.error(JSON.stringify({ error: 'Failed to read token decimals: ' + (e?.message || String(e)) }));
                process.exit(2);
            }

            try {
                const raw = await contract.balanceOf(address);
                const formatted = ethers.utils.formatUnits(raw, decimals);
                console.log(JSON.stringify({ balance: formatted, rawBalance: raw.toString(), decimals, contractAddress: contractAddr, address }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null } }));
                process.exit(2);
            }
        }

        if (action === 'parseEther') {
            const amount = normalizeHumanAmount(args[1]);
            if (!amount || Number.isNaN(Number(amount)) || !/^[0-9]+(\.[0-9]+)?$/.test(amount)) {
                console.error(JSON.stringify({ error: 'Invalid ETH amount' }));
                process.exit(2);
            }
            try {
                const wei = ethers.utils.parseEther(amount);
                console.log(JSON.stringify({ wei: wei.toString(), amount: amount }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: 'Invalid ETH amount' }));
                process.exit(2);
            }
        }

        if (action === 'signerAddress') {
            if (!process.env.PRIVATE_KEY) {
                console.error(JSON.stringify({ error: 'Missing PRIVATE_KEY in environment' }));
                process.exit(2);
            }

            try {
                const wallet = new ethers.Wallet(process.env.PRIVATE_KEY, provider);
                const signerAddr = await wallet.getAddress();
                console.log(JSON.stringify({ signerAddress: signerAddr, network: network }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null } }));
                process.exit(2);
            }
        }

        if (action === 'diagnose') {
            const result = {
                configuredNetwork: network,
                expectedChainId: EXPECTED_CHAIN_ID,
                detectedChainId: null,
                detectedNetwork: null,
                rpcHost,
                latestBlock: null,
                provider: 'UNKNOWN',
                usdtContract: process.env.USDT_CONTRACT_ADDRESS || null,
                usdtDecimals: null,
                error: null,
            };

            try {
                const net = await validateProviderNetwork(provider, network);
                result.detectedChainId = Number(net?.chainId ?? 0);
                result.detectedNetwork = String(net?.name || '').toLowerCase();
                result.provider = 'OK';
                result.latestBlock = await retryRpcOperation(() => provider.getBlockNumber(), 'getBlockNumber');

                if (result.usdtContract) {
                    const erc20Abi = ['function decimals() view returns (uint8)'];
                    const contract = new ethers.Contract(result.usdtContract, erc20Abi, provider);
                    result.usdtDecimals = Number(await contract.decimals());
                }
            } catch (error) {
                result.error = {
                    message: error?.message || String(error),
                    code: error?.code || 'RPC_DIAGNOSTIC_FAILED',
                };
                result.provider = 'ERROR';
            }

            console.log(JSON.stringify(result));
            process.exit(result.provider === 'OK' ? 0 : 2);
        }

        // Focused estimateGas action: returns only {"gasLimit": "<number>"} on stdout when successful.
        if (action === 'estimateGas') {
            const from = args[1];
            const to = args[2];
            const amountEth = args[3];

            if (!from || !ethers.utils.isAddress(from) || !to || !ethers.utils.isAddress(to) || !amountEth) {
                console.error(JSON.stringify({ error: 'estimateGas requires fromAddress, toAddress and amount' }));
                process.exit(2);
            }

            try {
                const normalized = normalizeHumanAmount(amountEth);
                if (!normalized) {
                    console.error(JSON.stringify({ error: 'Invalid ETH amount' }));
                    process.exit(2);
                }

                const value = ethers.utils.parseEther(normalized);
                const estimate = await retryRpcOperation(() => provider.estimateGas({ from: from, to: to, value }), 'estimateGas');
                console.log(JSON.stringify({ gasLimit: estimate.toString() }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null } }));
                process.exit(2);
            }
        }

        if (action === 'send') {
            // Strip known CLI flags (e.g. --network <name>) so positional args are stable
            const rawPositional = args.slice(1);
            const cleaned = [];
            for (let i = 0; i < rawPositional.length; i += 1) {
                if (rawPositional[i] === '--network') {
                    // skip the flag and its value (if present)
                    i += 1;
                    continue;
                }
                cleaned.push(rawPositional[i]);
            }

            const toAddress = cleaned[0];
            const amountEth = cleaned[1];
            const nonceArg = cleaned.length >= 3 ? cleaned[2] : null;

            if (!process.env.PRIVATE_KEY) {
                console.error(JSON.stringify({ error: 'Missing private key for send transaction' }));
                process.exit(2);
            }

            if (!toAddress || !ethers.utils.isAddress(toAddress)) {
                console.error(JSON.stringify({ error: 'Invalid destination address' }));
                process.exit(2);
            }

            const normalized = normalizeHumanAmount(amountEth);
            if (!normalized) {
                console.error(JSON.stringify({ error: 'Invalid ETH amount' }));
                process.exit(2);
            }

            const value = ethers.utils.parseEther(normalized);

            let wallet;
            try {
                wallet = new ethers.Wallet(process.env.PRIVATE_KEY, provider);
            } catch (we) {
                console.error(JSON.stringify({ error: { message: we?.message || String(we), name: we?.name || null, code: we?.code || null } }));
                process.exit(2);
            }

            try {
                const txRequest = { to: toAddress, value };

                // Only accept nonce if it looks like a non-negative integer
                if (nonceArg !== null && nonceArg !== undefined && nonceArg !== '' && /^[0-9]+$/.test(String(nonceArg))) {
                    txRequest.nonce = ethers.BigNumber.from(String(nonceArg));
                }

                const txResponse = await wallet.sendTransaction(txRequest);

                console.log(JSON.stringify({
                    txHash: txResponse.hash,
                    from: await wallet.getAddress(),
                    to: toAddress,
                    amountWei: value.toString(),
                    nonce: txRequest.nonce ? txRequest.nonce.toString() : null,
                    network: network
                }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null, data: e?.error?.data || null } }));
                process.exit(2);
            }
        }

        // Estimate gas for an ERC-20 token transfer
        if (action === 'estimateTokenGas') {
            // args: [contractAddress, fromAddress, toAddress, amountToken, decimals?]
            let contractAddr = args[1] || process.env.USDT_CONTRACT_ADDRESS || null;
            const fromAddress = args[2];
            const toAddress = args[3];
            const amountToken = args[4];
            const decimalsArg = args[5] || null;

            if (!contractAddr || !fromAddress || !toAddress || !amountToken) {
                console.error(JSON.stringify({ error: 'contractAddress, fromAddress, toAddress and amountToken required' }));
                process.exit(2);
            }

            if (!ethers.utils.isAddress(contractAddr) || !ethers.utils.isAddress(fromAddress) || !ethers.utils.isAddress(toAddress)) {
                console.error(JSON.stringify({ error: 'Invalid address parameter' }));
                process.exit(2);
            }

            const erc20Abi = [
                'event Transfer(address indexed from, address indexed to, uint256 value)',
                'function decimals() view returns (uint8)',
                'function transfer(address to, uint256 amount) returns (bool)'
            ];

            try {
                const contract = new ethers.Contract(contractAddr, erc20Abi, provider);
                let decimals = 6;

                try {
                    let maybeDecimals = decimalsArg ? parseInt(decimalsArg, 10) : await contract.decimals();
                    if (maybeDecimals && typeof maybeDecimals === 'object' && typeof maybeDecimals.toNumber === 'function') {
                        try { maybeDecimals = maybeDecimals.toNumber(); } catch (e) { }
                    }

                    const parsed = Number(maybeDecimals);
                    if (Number.isInteger(parsed) && parsed >= 0 && parsed <= 255) {
                        decimals = parsed;
                    } else {
                        decimals = 6;
                    }
                } catch (e) {
                    decimals = 6;
                }

                const amountUnits = ethers.utils.parseUnits(amountToken, decimals);
                const data = contract.interface.encodeFunctionData('transfer', [toAddress, amountUnits]);
                const gasLimit = await retryRpcOperation(() => provider.estimateGas({ to: contractAddr, from: fromAddress, data }), 'estimateGas');
                const feeData = await retryRpcOperation(() => provider.getFeeData(), 'getFeeData');

                let feeMode = 'legacy';
                let gasPrice = null;
                let maxFeePerGas = null;
                let maxPriorityFeePerGas = null;

                if (feeData && feeData.maxFeePerGas && feeData.maxPriorityFeePerGas) {
                    feeMode = 'eip1559';
                    maxFeePerGas = feeData.maxFeePerGas.toString();
                    maxPriorityFeePerGas = feeData.maxPriorityFeePerGas.toString();
                } else if (feeData && feeData.gasPrice) {
                    gasPrice = feeData.gasPrice.toString();
                } else {
                    console.error(JSON.stringify({ error: 'Current fee data is unavailable for USDT transfer estimate' }));
                    process.exit(2);
                }

                const costWei = feeMode === 'eip1559'
                    ? gasLimit.mul(ethers.BigNumber.from(maxFeePerGas))
                    : gasLimit.mul(ethers.BigNumber.from(gasPrice));

                console.log(JSON.stringify({
                    contractAddress: contractAddr,
                    from: fromAddress,
                    to: toAddress,
                    amountToken: amountToken,
                    decimals: decimals,
                    amountRaw: amountUnits.toString(),
                    gasLimit: gasLimit.toString(),
                    gasPrice: gasPrice ? gasPrice.toString() : null,
                    maxFeePerGas: maxFeePerGas,
                    maxPriorityFeePerGas: maxPriorityFeePerGas,
                    feeMode: feeMode,
                    estimatedGasCostWei: costWei.toString(),
                    estimatedGasCostEth: ethers.utils.formatEther(costWei)
                }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: 'estimateTokenGas failed: ' + (e?.message || String(e)), code: e?.code || null }));
                process.exit(2);
            }
        }

        if (action === 'feeData') {
            try {
                const feeData = await retryRpcOperation(() => provider.getFeeData(), 'getFeeData');
                console.log(JSON.stringify({
                    gasPrice: feeData.gasPrice ? feeData.gasPrice.toString() : null,
                    maxFeePerGas: feeData.maxFeePerGas ? feeData.maxFeePerGas.toString() : null,
                    maxPriorityFeePerGas: feeData.maxPriorityFeePerGas ? feeData.maxPriorityFeePerGas.toString() : null,
                    feeMode: feeData.maxFeePerGas && feeData.maxPriorityFeePerGas ? 'eip1559' : (feeData.gasPrice ? 'legacy' : null)
                }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: 'getFeeData failed: ' + (e?.message || String(e)), code: e?.code || null }));
                process.exit(2);
            }
        }

        if (action === 'gasPrice') {
            try {
                const gp = await provider.getGasPrice();
                console.log(JSON.stringify({ gasPrice: gp.toString() }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: 'getGasPrice failed: ' + (e?.message || String(e)), code: e?.code || null }));
                process.exit(2);
            }
        }

        if (action === 'sendToken') {
            let contractAddr = args[1] || process.env.USDT_CONTRACT_ADDRESS || null;
            const toAddress = args[2];
            const amountToken = args[3];
            const decimalsArg = args[4] ?? null;
            const gasLimitArg = args[5] ?? null;
            const feeModeArg = args[6] ?? null;
            const maxFeePerGasArg = args[7] ?? null;
            const maxPriorityFeePerGasArg = args[8] ?? null;
            const gasPriceArg = args[9] ?? null;
            const nonceArg = args[10] ?? null;

            if (!process.env.PRIVATE_KEY) {
                console.error(JSON.stringify({ error: 'Missing private key for token send transaction' }));
                process.exit(2);
            }

            if (!contractAddr) {
                console.error(JSON.stringify({ error: 'Token contract address not provided and USDT_CONTRACT_ADDRESS not set in environment' }));
                process.exit(2);
            }

            if (!toAddress || !ethers.utils.isAddress(toAddress)) {
                console.error(JSON.stringify({ error: 'Invalid destination address' }));
                process.exit(2);
            }

            if (!ethers.utils.isAddress(contractAddr)) {
                console.error(JSON.stringify({ error: 'Invalid token contract address' }));
                process.exit(2);
            }

            if (!amountToken || (Number.isNaN(Number(amountToken)) && amountToken.indexOf('.') === -1)) {
                console.error(JSON.stringify({ error: 'Invalid token amount' }));
                process.exit(2);
            }

            if (!gasLimitArg || !feeModeArg) {
                console.error(JSON.stringify({ error: 'Token send transaction requires gasLimit and feeMode' }));
                process.exit(2);
            }

            let wallet;
            try {
                wallet = new ethers.Wallet(process.env.PRIVATE_KEY, provider);
            } catch (we) {
                console.error(JSON.stringify({ error: { message: we?.message || String(we), name: we?.name || null, code: we?.code || null } }));
                process.exit(2);
            }

            const erc20Abi = [
                'function transfer(address to, uint256 amount) returns (bool)',
                'function decimals() view returns (uint8)'
            ];

            try {
                const contract = new ethers.Contract(contractAddr, erc20Abi, wallet);

                let decimals = 6;
                if (decimalsArg !== null && decimalsArg !== undefined && decimalsArg !== '') {
                    const parsed = parseInt(decimalsArg, 10);
                    if (!Number.isNaN(parsed) && parsed >= 0) {
                        decimals = parsed;
                    }
                } else {
                    try {
                        const d = await contract.decimals();
                        if (d !== undefined && d !== null) decimals = Number(d);
                    } catch (dd) {
                        // keep fallback
                    }
                }

                const amountUnits = ethers.utils.parseUnits(amountToken, decimals);
                const txOverrides = {
                    gasLimit: ethers.BigNumber.from(gasLimitArg)
                };

                if (feeModeArg === 'eip1559') {
                    if (!maxFeePerGasArg || !maxPriorityFeePerGasArg) {
                        console.error(JSON.stringify({ error: 'EIP-1559 send requires maxFeePerGas and maxPriorityFeePerGas' }));
                        process.exit(2);
                    }
                    txOverrides.maxFeePerGas = ethers.BigNumber.from(maxFeePerGasArg);
                    txOverrides.maxPriorityFeePerGas = ethers.BigNumber.from(maxPriorityFeePerGasArg);
                } else if (feeModeArg === 'legacy') {
                    if (!gasPriceArg) {
                        console.error(JSON.stringify({ error: 'Legacy send requires gasPrice' }));
                        process.exit(2);
                    }
                    txOverrides.gasPrice = ethers.BigNumber.from(gasPriceArg);
                } else {
                    console.error(JSON.stringify({ error: 'Unsupported fee mode for token send: ' + feeModeArg }));
                    process.exit(2);
                }

                if (nonceArg !== null && nonceArg !== undefined && nonceArg !== '') {
                    txOverrides.nonce = ethers.BigNumber.from(nonceArg);
                }

                const txResponse = await contract.transfer(toAddress, amountUnits, txOverrides);

                console.log(JSON.stringify({
                    txHash: txResponse.hash,
                    from: await wallet.getAddress(),
                    to: toAddress,
                    amountRaw: amountUnits.toString(),
                    decimals: decimals,
                    gasLimit: txOverrides.gasLimit.toString(),
                    nonce: nonceArg ?? null,
                    feeMode: feeModeArg,
                    network: network
                }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null, data: e?.error?.data || null } }));
                process.exit(2);
            }
        }

        if (action === 'receipt') {
            const txHash = args[1];
            if (!txHash) {
                console.error(JSON.stringify({ error: 'Transaction hash required' }));
                process.exit(2);
            }

            try {
                const receipt = await retryRpcOperation(() => provider.getTransactionReceipt(txHash), 'getTransactionReceipt');
                const networkInfo = await retryRpcOperation(() => provider.getNetwork(), 'getNetwork');
                console.log(JSON.stringify({
                    receipt,
                    confirmations: receipt?.confirmations ?? 0,
                    network: networkInfo.name ?? network,
                    chainId: networkInfo.chainId ?? null
                }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null } }));
                process.exit(2);
            }
        }

        if (action === 'history') {
            const address = args[1];
            if (!address) {
                console.error(JSON.stringify({ error: 'Address required' }));
                process.exit(2);
            }

            const limitNum = parseInt(args[2], 10) || 10;
            const fromBlockArg = args[3] ?? null;
            const fromBlock = fromBlockArg ? (typeof fromBlockArg === 'string' && fromBlockArg.startsWith('0x') ? fromBlockArg : '0x' + (parseInt(fromBlockArg, 10) || 0).toString(16)) : '0x0';

            // Convert limit to hex string as Alchemy expects hex for maxCount
            const maxCountHex = '0x' + limitNum.toString(16);

            // Build params for alchemy_getAssetTransfers
            const params = {
                fromBlock: fromBlock,
                toBlock: 'latest',
                category: ['external'],
                toAddress: address,
                maxCount: maxCountHex
            };

            // Call JSON-RPC method via provider.send
            let resp;
            try {
                resp = await provider.send('alchemy_getAssetTransfers', [params]);
            } catch (e) {
                console.error(JSON.stringify({ error: 'alchemy_getAssetTransfers failed: ' + (e?.message || String(e)) }));
                process.exit(2);
            }

            const transfers = resp.transfers || resp.result || resp || [];

            const mapped = (transfers || []).slice(0, limitNum).map(t => {
                let blockNumber = null;
                if (t.blockNum) {
                    try {
                        blockNumber = parseInt(t.blockNum, 16);
                    } catch (err) {
                        blockNumber = Number(t.blockNum) || null;
                    }
                } else if (t.blockNumber) {
                    blockNumber = Number(t.blockNumber) || null;
                }

                let valueRaw = t.value ?? (t.rawContract && t.rawContract.value) ?? '0';
                let valueFormatted = '0';
                try {
                    const bn = ethers.BigNumber.from(valueRaw.toString());
                    valueFormatted = ethers.utils.formatEther(bn);
                } catch (e) {
                    try {
                        valueFormatted = ethers.utils.formatEther(ethers.BigNumber.from(valueRaw));
                    } catch (e2) {
                        valueFormatted = String(valueRaw);
                    }
                }

                return {
                    hash: t.hash || t.transactionHash || null,
                    from: t.from || null,
                    to: t.to || null,
                    value: valueFormatted,
                    valueRaw: valueRaw,
                    blockNum: t.blockNum ?? (t.blockNumber ? '0x' + (Number(t.blockNumber).toString(16)) : null),
                    blockNumber: blockNumber
                };
            });

            console.log(JSON.stringify({ transactions: mapped }));
            process.exit(0);
        }

        if (action === 'tokenTransfers') {
            const contractAddr = args[1];
            const toAddr = args[2];
            if (!contractAddr || !toAddr) {
                console.error(JSON.stringify({ error: 'contractAddress and toAddress required' }));
                process.exit(2);
            }

            const limitNum = parseInt(args[3], 10) || 10;
            const fromBlockArg = args[4] ?? null;
            const toBlockArg = args[5] ?? null;

            const fromBlock = fromBlockArg ? (typeof fromBlockArg === 'string' && fromBlockArg.startsWith('0x') ? parseInt(fromBlockArg, 16) : (parseInt(fromBlockArg, 10) || 0)) : 0;

            const erc20Abi = [
                'event Transfer(address indexed from, address indexed to, uint256 value)',
                'function decimals() view returns (uint8)'
            ];

            const contract = new ethers.Contract(contractAddr, erc20Abi, provider);

            let decimals = 18;
            try {
                // Try reading decimals with retries to tolerate transient RPC/network issues
                decimals = await retryRpcOperation(() => contract.decimals(), 'contract.decimals');
            } catch (e) {
                // Log the failure but do not abort immediately. If this contract is the configured USDT
                // contract, fall back to 6 decimals. Otherwise abort as before.
                console.error(JSON.stringify({ error: 'Failed to read token decimals after retries: ' + (e?.message || String(e)) }));
                try {
                    const usdtEnv = process.env.USDT_CONTRACT_ADDRESS || '';
                    if (usdtEnv && usdtEnv.toLowerCase() === (contractAddr || '').toLowerCase()) {
                        // Known USDT contract — safe fallback to 6 decimals
                        decimals = 6;
                        console.error(JSON.stringify({ info: 'Falling back to decimals=6 for USDT contract ' + contractAddr }));
                    } else {
                        console.error(JSON.stringify({ error: 'Aborting tokenTransfers: decimals read failed and no USDT fallback available' }));
                        process.exit(2);
                    }
                } catch (_inner) {
                    console.error(JSON.stringify({ error: 'Aborting tokenTransfers: decimals read failed and fallback check failed' }));
                    process.exit(2);
                }
            }

            const transferTopic = ethers.utils.id('Transfer(address,address,uint256)');
            const toTopic = ethers.utils.hexZeroPad(ethers.utils.getAddress(toAddr), 32);

            let toBlock;
            if (toBlockArg) {
                toBlock = (typeof toBlockArg === 'string' && toBlockArg.startsWith('0x')) ? parseInt(toBlockArg, 16) : parseInt(toBlockArg, 10);
                if (isNaN(toBlock)) toBlock = await provider.getBlockNumber();
            } else {
                toBlock = await provider.getBlockNumber();
            }

            const CHUNK_SIZE = 10;
            const uniqueLogs = new Map();

            const fetchLogsForRange = async (startBlock, endBlock) => {
                const filter = {
                    address: contractAddr,
                    topics: [transferTopic, null, toTopic],
                    fromBlock: startBlock,
                    toBlock: endBlock
                };

                try {
                    const chunkLogs = await retryRpcOperation(() => provider.getLogs(filter), `getLogs ${startBlock}-${endBlock}`);
                    return chunkLogs || [];
                } catch (err) {
                    // On RPC/getLogs failure, fail loudly so callers (PHP) can detect and avoid assuming the chunk succeeded.
                    // Print structured error JSON to stderr and rethrow to let the top-level handler produce a non-zero exit.
                    console.error(JSON.stringify({ error: 'getLogs failed for range', range: `${startBlock}-${endBlock}`, message: err?.message || String(err) }));
                    throw err;
                }
            };

            for (let start = fromBlock; start <= toBlock; start += CHUNK_SIZE) {
                const end = Math.min(start + CHUNK_SIZE - 1, toBlock);
                const logs = await fetchLogsForRange(start, end);
                for (const log of logs) {
                    const key = `${log.transactionHash}:${log.logIndex ?? log.index ?? ''}`;
                    if (!uniqueLogs.has(key)) {
                        uniqueLogs.set(key, log);
                    }
                }
            }

            const iface = new ethers.utils.Interface(erc20Abi);
            const allLogs = Array.from(uniqueLogs.values());

            const decoded = allLogs.map(log => {
                const parsed = iface.parseLog(log);
                const valueRaw = parsed.args.value.toString();
                const valueNormalized = ethers.utils.formatUnits(parsed.args.value, decimals);
                return {
                    hash: log.transactionHash,
                    from: parsed.args.from,
                    to: parsed.args.to,
                    value: valueNormalized,
                    valueRaw: valueRaw,
                    blockNumber: log.blockNumber
                };
            });

            decoded.sort((a,b) => (b.blockNumber || 0) - (a.blockNumber || 0));

            const shouldReturnFullRange = fromBlockArg !== null || toBlockArg !== null;
            console.log(JSON.stringify({ transfers: shouldReturnFullRange ? decoded : decoded.slice(0, limitNum) }));
            process.exit(0);
        }

        if (action === 'getTransactionCount') {
            const address = args[1];
            const tag = args[2] || 'pending';
            if (!address) {
                console.error(JSON.stringify({ error: 'Address required' }));
                process.exit(2);
            }
            try {
                const txCount = await retryRpcOperation(() => provider.getTransactionCount(address, tag), 'getTransactionCount');
                console.log(JSON.stringify({ transactionCount: txCount.toString() }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null } }));
                process.exit(2);
            }
        }

        if (action === 'getTransactionByHash') {
            const txHash = args[1];
            if (!txHash) {
                console.error(JSON.stringify({ error: 'Transaction hash required' }));
                process.exit(2);
            }
            try {
                const tx = await retryRpcOperation(() => provider.getTransaction(txHash), 'getTransactionByHash');
                console.log(JSON.stringify({ transaction: tx ? { hash: tx.hash, nonce: tx.nonce ? tx.nonce.toString() : null, from: tx.from, to: tx.to, blockHash: tx.blockHash, blockNumber: tx.blockNumber ? tx.blockNumber.toString() : null } : null }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null } }));
                process.exit(2);
            }
        }

        if (action === 'block' || action === 'getBlock') {
            const blockIdentifier = args[1];
            if (!blockIdentifier) {
                console.error(JSON.stringify({ error: 'Block identifier required' }));
                process.exit(2);
            }
            try {
                let blockTag = blockIdentifier;

                if (/^\d+$/.test(blockIdentifier)) {
                    const num = Number(blockIdentifier);
                    if (!Number.isSafeInteger(num)) {
                        console.error(JSON.stringify({ error: 'Block number is not a safe integer' }));
                        process.exit(2);
                    }
                    blockTag = num;
                }

                const block = await retryRpcOperation(() => provider.getBlock(blockTag), 'getBlock');
                const txHashes = Array.isArray(block?.transactions)
                    ? block.transactions.map((tx) => typeof tx === 'string' ? tx : (tx?.hash ?? null)).filter(Boolean)
                    : [];
                const blockPayload = block ? {
                    number: block.number,
                    hash: block.hash,
                    parentHash: block.parentHash,
                    timestamp: block.timestamp,
                    transactions: block.transactions ?? [],
                    transactionHashes: txHashes,
                } : null;

                console.log(JSON.stringify({
                    block: blockPayload,
                    number: blockPayload?.number ?? null,
                    hash: blockPayload?.hash ?? null,
                    parentHash: blockPayload?.parentHash ?? null,
                    timestamp: blockPayload?.timestamp ?? null,
                    transactions: blockPayload?.transactions ?? [],
                    transactionHashes: blockPayload?.transactionHashes ?? [],
                }));
                process.exit(0);
            } catch (e) {
                console.error(JSON.stringify({ error: { message: e?.message || String(e), code: e?.code || null } }));
                process.exit(2);
            }
        }

        if (action === 'blockNumber') {
            const bn = await provider.getBlockNumber();
            console.log(JSON.stringify({ blockNumber: bn }));
            process.exit(0);
        }

        console.error(JSON.stringify({ error: 'Unknown action: ' + action }));
        process.exit(2);
    } catch (err) {
        console.error(JSON.stringify({ error: err?.message || String(err) }));
        process.exit(3);
    }
})();
