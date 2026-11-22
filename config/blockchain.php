<?php

return [
    'enabled' => (bool) env('BLOCKCHAIN_ENABLED', false),
    'network' => env('BLOCKCHAIN_NETWORK', 'sepolia'),
    // «public»: the real Sepolia testnet (https). «local-simulation»: the project's local network compatible
    // with Sepolia (loopback only). Same chain id, never interchangeable; see docs/BLOCKCHAIN_IMPLEMENTATION.md.
    'environment' => env('BLOCKCHAIN_ENVIRONMENT', 'public'),
    // Contract addresses deployed on the local simulation (written by the local deployment). A reference to one
    // of them is a local record whatever the environment configured later, and is never shown as public.
    'simulation_contracts' => array_values(array_filter(array_map(
        fn ($address) => strtolower(trim($address)), explode(',', (string) env('BLOCKCHAIN_SIMULATION_CONTRACTS', ''))
    ))),
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
