<?php

return [
    // The network name used by the EthereumService and node script
    'network' => env('ETHEREUM_NETWORK', 'sepolia'),

    // Confirmation threshold used for marking on-chain payments as confirmed.
    // Defaults to 2 on Sepolia testnet and 12 on mainnet, overridable via ETH_CONFIRMATION_THRESHOLD.
    'confirmation_threshold' => (int) env('ETH_CONFIRMATION_THRESHOLD', (strtolower((string)env('ETHEREUM_NETWORK', 'sepolia')) === 'sepolia') ? 2 : 12),
];
