import { ethers } from 'ethers';

function rpcHostForNetwork(network){
    if(network === 'sepolia') return 'eth-sepolia.g.alchemy.com';
    return `eth-${network}.g.alchemy.com`;
}

(async ()=>{
    try{
        const args = process.argv.slice(2);
        const contractAddr = args[0];
        const from = args[1];
        const to = args[2];
        const amount = args[3]; // human readable, e.g. "1"
        const network = args[4] || (process.env.ETHEREUM_NETWORK || 'sepolia');

        const apiKey = process.env.ALCHEMY_API_KEY;
        if(!apiKey){
            console.error(JSON.stringify({error:'Missing ALCHEMY_API_KEY in environment'}));
            process.exit(2);
        }

        const rpcHost = rpcHostForNetwork(network);
        const provider = new ethers.providers.JsonRpcProvider(`https://${rpcHost}/v2/${apiKey}`, network);

        const erc20Abi = [
            'function symbol() view returns (string)',
            'function decimals() view returns (uint8)',
            'function balanceOf(address) view returns (uint256)',
            'function transfer(address to, uint256 amount) returns (bool)'
        ];

        const contract = new ethers.Contract(contractAddr, erc20Abi, provider);

        const symbol = await contract.symbol().catch(e => null);
        const decimals = await contract.decimals().catch(e => null);

        const amountUnits = decimals !== null ? ethers.utils.parseUnits(amount, decimals) : ethers.BigNumber.from('0');

        // build data
        const iface = new ethers.utils.Interface(['function transfer(address to, uint256 amount)']);
        const data = iface.encodeFunctionData('transfer',[to, amountUnits]);

        const gasPrice = await provider.getGasPrice();
        let estimateGas = null;
        try{
            estimateGas = await provider.estimateGas({ from, to: contractAddr, data });
        }catch(e){
            estimateGas = { error: e?.message || String(e) };
        }

        console.log(JSON.stringify({
            contract: contractAddr,
            symbol: symbol ?? null,
            decimals: decimals !== null ? Number(decimals) : null,
            amount: amount,
            amountUnits: amountUnits.toString(),
            from,
            to,
            gasPrice: gasPrice.toString(),
            estimateGas: estimateGas && estimateGas.toString ? estimateGas.toString() : estimateGas,
            network
        }));
        process.exit(0);
    }catch(e){
        console.error(JSON.stringify({error: e?.message || String(e)}));
        process.exit(2);
    }
})();
