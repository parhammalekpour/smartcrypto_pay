import https from 'https';
import dns from 'dns';
import { ethers } from 'ethers';

// Node helper for simple read-only Ethereum actions via Alchemy
// Usage:
//   node scripts/ethereum-client.js isAddress <address>
//   node scripts/ethereum-client.js balance <address>
//   node scripts/ethereum-client.js history <address> [limit] [fromBlock] [network]
//   node scripts/ethereum-client.js tokenTransfers <contract> <toAddress> [limit] [fromBlock] [network]
//   node scripts/ethereum-client.js receipt <txHash>
//   node scripts/ethereum-client.js blockNumber

const RETRYABLE_RPC_ERROR_CODES = new Set(['EAI_FAIL', 'ECONNRESET', 'ETIMEDOUT', 'ECONNREFUSED', 'SERVER_ERROR']);
const RETRYABLE_RPC_ERROR_MESSAGES = [/EAI_FAIL/i, /ECONNRESET/i, /ETIMEDOUT/i, /ECONNREFUSED/i, /SERVER_ERROR/i, /ENOTFOUND/i, /EAI_AGAIN/i];

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

    return RETRYABLE_RPC_ERROR_MESSAGES.some((pattern) => pattern.test(message));
};

const httpsProbeHost = async (rpcHost) => {
    return new Promise((resolve, reject) => {
        const req = https.request({
            hostname: rpcHost,
            method: 'HEAD',
            path: '/',
            timeout: 5000,
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

    for (let attempt = 1; attempt <= 3; attempt += 1) {
        try {
            return await operation();
        } catch (error) {
            lastError = error;
            if (attempt === 3 || !shouldRetryError(error)) {
                throw error;
            }
            await wait(250 * attempt);
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

const createProvider = (apiKey, network, rpcHost) => {
    const rpcUrl = `https://${rpcHost}/v2/${apiKey}`;
    return new ethers.providers.JsonRpcProvider(rpcUrl, network);
};

(async () => {
    try {
        const args = process.argv.slice(2);
        const action = args[0];

        const apiKey = process.env.ALCHEMY_API_KEY;
        if (!apiKey) {
            console.error(JSON.stringify({ error: 'Missing ALCHEMY_API_KEY in environment' }));
            process.exit(2);
        }

        const network = args.includes('--network') ? args[args.indexOf('--network') + 1] : (process.env.ETHEREUM_NETWORK || 'sepolia');
        const rpcHost = rpcHostForNetwork(network);
        const provider = createProvider(apiKey, network, rpcHost);

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

        if (action === 'parseEther') {
            const amount = args[1];
            if (!amount || (Number.isNaN(Number(amount)) && amount.indexOf('.') === -1)) {
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
            const fromAddress = args[1];
            const toAddress = args[2];
            const amountEth = args[3];
            const result = {
                network: network,
                chainId: null,
                rpcHost,
                dnsLookupSuccess: false,
                resolvedAddress: null,
                dnsLookupErrorCode: null,
                dnsLookupErrorMessage: null,
                fallbackResolverUsed: false,
                httpsProbeSuccess: false,
                httpsProbeErrorMessage: null,
                rpcConnectivitySuccess: false,
                rpcError: null,
                from: fromAddress || null,
                to: toAddress || null,
                amountEth: amountEth || null,
                amountWei: null,
                senderBalanceWei: null,
                senderBalanceEth: null,
                gasPrice: null,
                estimate: null,
                estimateError: null
            };

            const dnsDiagnostics = await probeDns(rpcHost);
            result.dnsLookupSuccess = dnsDiagnostics.dnsLookupSuccess;
            result.resolvedAddress = dnsDiagnostics.resolvedAddress;
            result.dnsLookupErrorCode = dnsDiagnostics.dnsLookupErrorCode;
            result.dnsLookupErrorMessage = dnsDiagnostics.dnsLookupErrorMessage;
            result.fallbackResolverUsed = dnsDiagnostics.fallbackResolverUsed;
            result.httpsProbeSuccess = dnsDiagnostics.httpsProbeSuccess;
            result.httpsProbeErrorMessage = dnsDiagnostics.httpsProbeErrorMessage;

            try {
                const net = await retryRpcOperation(() => provider.getNetwork(), 'getNetwork');
                result.chainId = net.chainId;
                result.rpcConnectivitySuccess = true;
            } catch (e) {
                result.rpcError = e?.message || String(e);
                result.estimateError = { message: 'RPC connectivity failed: ' + result.rpcError, code: 'RPC_CONNECTIVITY_FAILED' };
                console.log(JSON.stringify(result));
                process.exit(0);
            }

            if (fromAddress && !ethers.utils.isAddress(fromAddress)) {
                result.estimateError = { message: 'Invalid from address', code: 'INVALID_FROM' };
                console.log(JSON.stringify(result));
                process.exit(0);
            }

            if (toAddress && !ethers.utils.isAddress(toAddress)) {
                result.estimateError = { message: 'Invalid to address', code: 'INVALID_TO' };
                console.log(JSON.stringify(result));
                process.exit(0);
            }

            if (!amountEth) {
                result.estimateError = { message: 'Missing amount', code: 'MISSING_AMOUNT' };
                console.log(JSON.stringify(result));
                process.exit(0);
            }

            try {
                const value = ethers.utils.parseEther(amountEth);
                result.amountWei = value.toString();
            } catch (e) {
                result.estimateError = { message: 'parseEther failed: ' + (e?.message || String(e)), code: e?.code || null };
                console.log(JSON.stringify(result));
                process.exit(0);
            }

            try {
                const bal = await retryRpcOperation(() => provider.getBalance(fromAddress), 'getBalance');
                result.senderBalanceWei = bal.toString();
                result.senderBalanceEth = ethers.utils.formatEther(bal);
            } catch (e) {
                result.rpcError = e?.message || String(e);
                result.estimateError = { message: 'getBalance failed: ' + result.rpcError, code: e?.code || null };
                console.log(JSON.stringify(result));
                process.exit(0);
            }

            try {
                const gp = await retryRpcOperation(() => provider.getGasPrice(), 'getGasPrice');
                result.gasPrice = gp.toString();
            } catch (e) {
                result.rpcError = e?.message || String(e);
                result.estimateError = { message: 'getGasPrice failed: ' + result.rpcError, code: e?.code || null };
                console.log(JSON.stringify(result));
                process.exit(0);
            }

            try {
                const value = ethers.BigNumber.from(result.amountWei);
                const gasLimit = await retryRpcOperation(() => provider.estimateGas({ from: fromAddress, to: toAddress, value }), 'estimateGas');
                result.estimate = { gasLimit: gasLimit.toString() };
            } catch (e) {
                result.rpcError = e?.message || String(e);
                result.estimateError = {
                    message: e?.message || String(e),
                    code: e?.code || null,
                    reason: e?.reason || null,
                    data: e?.error?.data || e?.data || null,
                    rpcError: e?.error?.body || null
                };
            }

            console.log(JSON.stringify(result));
            process.exit(0);
        }

        if (action === 'send') {
            const toAddress = args[1];
            const amountEth = args[2];
            if (!process.env.PRIVATE_KEY) {
                console.error(JSON.stringify({ error: 'Missing private key for send transaction' }));
                process.exit(2);
            }
            if (!toAddress || !ethers.utils.isAddress(toAddress)) {
                console.error(JSON.stringify({ error: 'Invalid destination address' }));
                process.exit(2);
            }

            const value = ethers.utils.parseEther(amountEth);
            let wallet;
            try {
                wallet = new ethers.Wallet(process.env.PRIVATE_KEY, provider);
            } catch (we) {
                console.error(JSON.stringify({ error: { message: we?.message || String(we), name: we?.name || null, code: we?.code || null } }));
                process.exit(2);
            }

            try {
                const txResponse = await wallet.sendTransaction({
                    to: toAddress,
                    value
                });

                console.log(JSON.stringify({
                    txHash: txResponse.hash,
                    from: await wallet.getAddress(),
                    to: toAddress,
                    amountWei: value.toString(),
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
            const fromBlock = fromBlockArg ? (typeof fromBlockArg === 'string' && fromBlockArg.startsWith('0x') ? fromBlockArg : (parseInt(fromBlockArg, 10) || 0)) : 0;

            // Use minimal ERC-20 ABI for decoding Transfer event and reading decimals
            const erc20Abi = [
                'event Transfer(address indexed from, address indexed to, uint256 value)',
                'function decimals() view returns (uint8)'
            ];

            const contract = new ethers.Contract(contractAddr, erc20Abi, provider);

            let decimals = 18;
            try {
                decimals = await contract.decimals();
            } catch (e) {
                console.error(JSON.stringify({ error: 'Failed to read token decimals: ' + (e?.message || String(e)) }));
                process.exit(2);
            }

            const transferTopic = ethers.utils.id('Transfer(address,address,uint256)');
            const toTopic = ethers.utils.hexZeroPad(ethers.utils.getAddress(toAddr), 32);

            const filter = {
                address: contractAddr,
                topics: [transferTopic, null, toTopic],
                fromBlock: fromBlock
            };

            const logs = await provider.getLogs(filter);

            const iface = new ethers.utils.Interface(erc20Abi);

            const decoded = logs.map(log => {
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

            console.log(JSON.stringify({ transfers: decoded.slice(0, limitNum) }));
            process.exit(0);
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
