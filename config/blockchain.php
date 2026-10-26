<?php

return [
    'enabled' => (bool) env('BLOCKCHAIN_ENABLED', false),
    'network' => env('BLOCKCHAIN_NETWORK', 'sepolia'),
    'driver' => env('BLOCKCHAIN_DRIVER', 'ethereum'),
    'rpc_url' => env('BLOCKCHAIN_RPC_URL', ''),
    'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', 11155111),
    'private_key' => env('BLOCKCHAIN_PRIVATE_KEY', ''),
    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS', ''),
    'confirmations' => max(1, (int) env('BLOCKCHAIN_CONFIRMATIONS', 1)),
    'deployment_block' => (int) env('BLOCKCHAIN_DEPLOYMENT_BLOCK', 0),
    'node' => env('BLOCKCHAIN_NODE_BINARY', 'node'),
    'timeout' => 15,
    'batch_size' => 25,
];
