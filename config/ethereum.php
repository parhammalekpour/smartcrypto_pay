<?php

return [
    // The active Ethereum network used by the EthereumService and node script.
    'network' => env('ETHEREUM_NETWORK', 'sepolia'),

    // Canonical RPC URL used for all blockchain reads/writes. If unset, we derive the Alchemy Sepolia URL.
    'rpc_url' => env('ETHEREUM_RPC_URL') ?: null,

    // Alchemy API key used for the canonical provider URL and child-process RPC access.
    'alchemy_api_key' => env('ALCHEMY_API_KEY'),

    // Sepolia chain ID used for fast-fail provider validation.
    'chain_id' => 11155111,

    // Ethereum Sepolia ERC-20 test USDT contract address.
    'usdt_contract_address' => env('USDT_CONTRACT_ADDRESS'),

    // Small safety margin applied to gas estimates before broadcast.
    'gas_safety_margin_bps' => (int) env('ETH_GAS_SAFETY_MARGIN_BPS', 5),

    // Confirmation threshold used for marking on-chain payments as confirmed.
    // Defaults to 2 on Sepolia testnet and 12 on mainnet, overridable via ETH_CONFIRMATION_THRESHOLD.
    'confirmation_threshold' => (int) env('ETH_CONFIRMATION_THRESHOLD', (strtolower((string) env('ETHEREUM_NETWORK', 'sepolia')) === 'sepolia') ? 2 : 12),

    // How many pending txs (deposits/withdrawals) to check per confirmation job run.
    // Reducing this keeps each scheduled job short and prevents long-running jobs/backlog.
    'confirmation_scan_limit' => (int) env('ETH_CONFIRMATION_SCAN_LIMIT', 200),

    // How many wallets to process per scheduled BlockchainScanJob invocation.
    // Keeps each scan job bounded so the queue does not build up when there are many wallets.
    'scan_wallets_per_job' => (int) env('ETH_SCAN_WALLETS_PER_JOB', 20),

    // Maximum age in seconds before a tx_hash with no receipt is treated as dropped.
    'pending_timeout' => (int) env('ETH_PENDING_TX_TIMEOUT', 1800),

    // Recent canonical-chain re-check window in blocks used by confirmation jobs.
    'canonical_recheck_blocks' => (int) env('ETH_CANONICAL_RECHECK_BLOCKS', 64),
];
