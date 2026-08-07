import { ethers } from 'ethers';

// Node helper for simple read-only Ethereum actions via Alchemy
// Usage:
//   node scripts/ethereum-client.js isAddress <address>
//   node scripts/ethereum-client.js balance <address>
//   node scripts/ethereum-client.js history <address> [limit] [fromBlock] [network]
//   node scripts/ethereum-client.js tokenTransfers <contract> <toAddress> [limit] [fromBlock] [network]
//   node scripts/ethereum-client.js blockNumber

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
        const provider = new ethers.providers.AlchemyProvider(network, apiKey);

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
