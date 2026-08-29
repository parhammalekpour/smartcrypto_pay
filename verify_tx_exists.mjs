import https from 'https';

const RPC_HOST = 'eth-sepolia.g.alchemy.com';
const API_KEY = 'alch_XXcxdCO7xnwwifpu4zoQE';

const TX_HASH = '0xeedffea149f7fea90436d6637430e138a9e9868f586840ecae43d57f4597ecbc';

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
  console.log('=== TRANSACTION VERIFICATION ===\n');

  console.log(`TX Hash: ${TX_HASH}\n`);

  // Get full transaction
  console.log('1. Full Transaction:');
  let tx = await makeJsonRpcCall('eth_getTransaction', [TX_HASH]);
  if (tx.result) {
    console.log(`   Found: YES`);
    console.log(`   From: ${tx.result.from}`);
    console.log(`   To: ${tx.result.to}`);
    console.log(`   BlockNumber: ${tx.result.blockNumber ? parseInt(tx.result.blockNumber, 16) : 'pending'}`);
  } else {
    console.log(`   Found: NO`);
    console.log(`   Error: ${tx.error?.message}`);
  }

  // Get receipt
  console.log('\n2. Transaction Receipt:');
  let receipt = await makeJsonRpcCall('eth_getTransactionReceipt', [TX_HASH]);
  if (receipt.result) {
    const blockNum = parseInt(receipt.result.blockNumber, 16);
    const txIndex = parseInt(receipt.result.transactionIndex, 16);
    const status = receipt.result.status === '0x1' ? 'SUCCESS' : (receipt.result.status === '0x0' ? 'FAILED' : 'UNKNOWN');
    
    console.log(`   Found: YES`);
    console.log(`   Status: ${status}`);
    console.log(`   BlockNumber: ${blockNum} (0x${blockNum.toString(16)})`);
    console.log(`   TransactionIndex: ${txIndex}`);
    console.log(`   Logs: ${receipt.result.logs.length}`);
    console.log(`   GasUsed: ${parseInt(receipt.result.gasUsed, 16)}`);
    
    // If it's block 11521497, verify
    if (blockNum === 11521497) {
      console.log(`   ✓ MATCHES expected block 11521497`);
    } else {
      console.log(`   ✗ BLOCK MISMATCH - expected 11521497, got ${blockNum}`);
    }

    // Show logs
    console.log(`\n3. Logs in Receipt:`);
    receipt.result.logs.forEach((log, i) => {
      console.log(`   Log ${i}:`);
      console.log(`     - Address: ${log.address}`);
      console.log(`     - Topics: ${log.topics.length}`);
      console.log(`     - Data: ${log.data}`);
      if (log.topics.length >= 3) {
        const from = '0x' + (log.topics[1]?.slice(-40) || 'null');
        const to = '0x' + (log.topics[2]?.slice(-40) || 'null');
        console.log(`     - From: ${from}`);
        console.log(`     - To: ${to}`);
      }
    });
  } else {
    console.log(`   Found: NO`);
    console.log(`   Error: ${receipt.error?.message}`);
    console.log(`   \n   *** TRANSACTION DOES NOT EXIST ON BLOCKCHAIN ***`);
  }

})().catch(console.error);
