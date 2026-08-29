import https from 'https';

const RPC_HOST = 'eth-sepolia.g.alchemy.com';
const API_KEY = 'alch_XXcxdCO7xnwwifpu4zoQE';

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
  console.log('=== DETAILED getLogs DIAGNOSTIC ===\n');

  const block = 11521497;
  const transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
  const toTopic = '0x0000000000000000000000006ab6f22afcca3b4aedc26e834815d47cca590fcd';

  // Test 1: getLogs with all topics (as scanner does)
  console.log('Test 1: getLogs WITH topics filter [Transfer, null, toAddress]');
  let result1 = await makeJsonRpcCall('eth_getLogs', [{
    address: USDT_CONTRACT,
    topics: [transferTopic, null, toTopic],
    fromBlock: '0x' + block.toString(16),
    toBlock: '0x' + block.toString(16)
  }]);
  console.log(`  Result: ${result1.result.length} logs`);

  // Test 2: getLogs with only contract and Transfer topic
  console.log('\nTest 2: getLogs WITH only Transfer topic');
  let result2 = await makeJsonRpcCall('eth_getLogs', [{
    address: USDT_CONTRACT,
    topics: [transferTopic],
    fromBlock: '0x' + block.toString(16),
    toBlock: '0x' + block.toString(16)
  }]);
  console.log(`  Result: ${result2.result.length} logs`);

  // Test 3: getLogs with only contract address
  console.log('\nTest 3: getLogs with ONLY contract address (no topics)');
  let result3 = await makeJsonRpcCall('eth_getLogs', [{
    address: USDT_CONTRACT,
    fromBlock: '0x' + block.toString(16),
    toBlock: '0x' + block.toString(16)
  }]);
  console.log(`  Result: ${result3.result.length} logs`);
  if (result3.result.length > 0) {
    console.log(`  First log:`, JSON.stringify(result3.result[0], null, 2));
  }

  // Test 4: Try with both address array and topics
  console.log('\nTest 4: getLogs with address as array');
  let result4 = await makeJsonRpcCall('eth_getLogs', [{
    address: [USDT_CONTRACT],
    topics: [transferTopic, null, toTopic],
    fromBlock: '0x' + block.toString(16),
    toBlock: '0x' + block.toString(16)
  }]);
  console.log(`  Result: ${result4.result.length} logs`);

  // Test 5: Wider range - try blocks around it
  console.log('\nTest 5: getLogs with wider range (11521490-11521500)');
  let result5 = await makeJsonRpcCall('eth_getLogs', [{
    address: USDT_CONTRACT,
    topics: [transferTopic, null, toTopic],
    fromBlock: '0x' + (block - 7).toString(16),
    toBlock: '0x' + (block + 3).toString(16)
  }]);
  console.log(`  Result: ${result5.result.length} logs`);

  // Test 6: Get all transfers from that contract in that block
  console.log('\nTest 6: getLogs for ALL transfers from contract in that block');
  let result6 = await makeJsonRpcCall('eth_getLogs', [{
    address: USDT_CONTRACT,
    topics: [transferTopic],
    fromBlock: '0x' + block.toString(16),
    toBlock: '0x' + block.toString(16)
  }]);
  console.log(`  Result: ${result6.result.length} logs`);
  if (result6.result.length > 0) {
    result6.result.forEach((log, i) => {
      console.log(`  Log ${i}: tx=${log.transactionHash}, from=0x${log.topics[1]?.slice(-40)}, to=0x${log.topics[2]?.slice(-40)}`);
    });
  }

  // Test 7: Check if the issue is address case sensitivity
  console.log('\nTest 7: Checking address case sensitivity');
  const USDT_CONTRACT_lower = USDT_CONTRACT.toLowerCase();
  let result7 = await makeJsonRpcCall('eth_getLogs', [{
    address: USDT_CONTRACT_lower,
    topics: [transferTopic, null, toTopic],
    fromBlock: '0x' + block.toString(16),
    toBlock: '0x' + block.toString(16)
  }]);
  console.log(`  Result with lowercase: ${result7.result.length} logs`);

})().catch(console.error);
