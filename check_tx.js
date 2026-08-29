const https = require('https');

const RPC_HOST = 'eth-sepolia.g.alchemy.com';
const API_KEY = 'alch_XXcxdCO7xnwwifpu4zoQE';
const RPC_URL = `https://${RPC_HOST}/v2/${API_KEY}`;

const TX_HASH = '0xeedffea149f7fea90436d6637430e138a9e9868f586840ecae43d57f4597ecbc';
const USDT_CONTRACT = '0xDAedAc477118680F85B7812AF3Dec4be3F3A86C9';
const WALLET = '0x6AB6f22AfCca3b4AEdc26E834815d47cca590Fcd';

function makeJsonRpcCall(method, params) {
  return new Promise((resolve, reject) => {
    const payload = JSON.stringify({
      jsonrpc: '2.0',
      id: 1,
      method: method,
      params: params
    });

    const options = {
      hostname: RPC_HOST,
      path: `/v2/${API_KEY}`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': payload.length
      }
    };

    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          const result = JSON.parse(data);
          resolve(result);
        } catch (e) {
          reject(new Error(`Failed to parse JSON: ${e.message}`));
        }
      });
    });

    req.on('error', reject);
    req.write(payload);
    req.end();
  });
}

(async () => {
  console.log('=== TX ON-CHAIN VERIFICATION ===\n');

  // Get receipt
  console.log('1. Transaction Receipt:');
  let receipt = await makeJsonRpcCall('eth_getTransactionReceipt', [TX_HASH]);
  if (receipt.result) {
    console.log(`   Status: ${receipt.result.status === '0x1' ? 'SUCCESS' : 'FAILED'}`);
    console.log(`   Block: ${parseInt(receipt.result.blockNumber, 16)}`);
    console.log(`   Log Count: ${receipt.result.logs.length}`);
    
    // Look for Transfer logs to WALLET
    console.log('\n2. Transfer Logs Analysis:');
    const transferLogs = receipt.result.logs.filter(log => {
      // Check if this is the USDT contract
      if (log.address.toLowerCase() !== USDT_CONTRACT.toLowerCase()) {
        return false;
      }
      // Check if there's a Transfer event (topic0)
      if (!log.topics[0] || log.topics[0] !== '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef') {
        return false;
      }
      // Check if recipient is WALLET
      if (!log.topics[2]) return false;
      const toAddress = '0x' + log.topics[2].slice(-40);
      return toAddress.toLowerCase() === WALLET.toLowerCase();
    });

    if (transferLogs.length > 0) {
      console.log(`   TRANSFER LOG FOUND: YES (${transferLogs.length})`);
      transferLogs.forEach((log, i) => {
        console.log(`\n   Log ${i + 1}:`);
        console.log(`   - Index: ${log.logIndex}`);
        console.log(`   - Topics: ${log.topics.length}`);
        if (log.topics[1]) {
          const from = '0x' + log.topics[1].slice(-40);
          console.log(`   - From: ${from}`);
        }
        if (log.topics[2]) {
          const to = '0x' + log.topics[2].slice(-40);
          console.log(`   - To: ${to}`);
        }
        console.log(`   - Data: ${log.data}`);
        
        // Parse the amount
        if (log.data && log.data.length === 66) {
          const amountHex = log.data;
          const amountBigInt = BigInt(amountHex);
          console.log(`   - Amount (raw): ${amountBigInt.toString()}`);
          console.log(`   - Amount (6 decimals): ${(amountBigInt / BigInt(1000000)).toString()}`);
        }
      });
    } else {
      console.log(`   TRANSFER LOG FOUND: NO`);
      console.log(`   Logs in receipt: ${receipt.result.logs.length}`);
      if (receipt.result.logs.length > 0) {
        console.log(`   First log contract: ${receipt.result.logs[0].address}`);
        console.log(`   First log topic0: ${receipt.result.logs[0].topics[0]}`);
      }
    }
  } else {
    console.log(`   ERROR: ${receipt.error?.message || 'Receipt not found'}`);
  }

  // Get current block
  console.log('\n3. Current Block:');
  let blockNum = await makeJsonRpcCall('eth_blockNumber', []);
  const currentBlock = parseInt(blockNum.result, 16);
  console.log(`   Current: ${currentBlock}`);
  console.log(`   TX Block: 11521497`);
  console.log(`   Confirmations: ${currentBlock - 11521497 + 1}`);

  // Simulate getLogs call
  console.log('\n4. getLogs Filter Simulation:');
  const transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
  const toTopic = '0x0000000000000000000000006ab6f22afcca3b4aedc26e834815d47cca590fcd';
  
  console.log(`   Contract: ${USDT_CONTRACT}`);
  console.log(`   Topics[0] (Transfer): ${transferTopic}`);
  console.log(`   Topics[2] (to): ${toTopic}`);
  console.log(`   FromBlock: 11521497`);
  console.log(`   ToBlock: 11521497`);

  let logs = await makeJsonRpcCall('eth_getLogs', [{
    address: USDT_CONTRACT,
    topics: [transferTopic, null, toTopic],
    fromBlock: '0xafda49',  // 11521497 in hex
    toBlock: '0xafda49'
  }]);

  if (logs.result) {
    console.log(`   Logs returned: ${logs.result.length}`);
    if (logs.result.length > 0) {
      console.log('   GETLOGS DETECTS TX: YES');
    } else {
      console.log('   GETLOGS DETECTS TX: NO');
    }
  } else {
    console.log(`   ERROR: ${logs.error?.message}`);
  }

})().catch(console.error);
