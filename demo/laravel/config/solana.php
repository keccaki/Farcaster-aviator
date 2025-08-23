<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Solana Network Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Solana blockchain integration.
    | You can switch between mainnet, testnet, and devnet environments.
    |
    */

    'network' => env('SOLANA_NETWORK', 'mainnet'),

    'networks' => [
        'mainnet' => [
            'rpc_endpoint' => env('SOLANA_RPC_ENDPOINT', 'https://api.mainnet-beta.solana.com'),
            'ws_endpoint' => env('SOLANA_WS_ENDPOINT', 'wss://api.mainnet-beta.solana.com'),
            'explorer_url' => 'https://solscan.io',
        ],
        'testnet' => [
            'rpc_endpoint' => 'https://api.testnet.solana.com',
            'ws_endpoint' => 'wss://api.testnet.solana.com',
            'explorer_url' => 'https://solscan.io',
        ],
        'devnet' => [
            'rpc_endpoint' => 'https://api.devnet.solana.com',
            'ws_endpoint' => 'wss://api.devnet.solana.com',
            'explorer_url' => 'https://solscan.io',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for wallet generation and management.
    |
    */

    'wallet' => [
        'master_seed' => env('SOLANA_MASTER_SEED'),
        'encryption_key' => env('SOLANA_ENCRYPTION_KEY', env('APP_KEY')),
        'derivation_path_prefix' => "m/44'/501'",
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for supported tokens on Solana.
    |
    */

    'tokens' => [
        'usdt' => [
            'mint_address' => env('USDT_MINT_ADDRESS', 'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB'),
            'decimals' => 6,
            'symbol' => 'USDT',
            'name' => 'Tether USD',
        ],
        'sol' => [
            'mint_address' => null, // Native SOL
            'decimals' => 9,
            'symbol' => 'SOL',
            'name' => 'Solana',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for transaction processing.
    |
    */

    'transactions' => [
        'required_confirmations' => env('SOLANA_REQUIRED_CONFIRMATIONS', 32),
        'max_retries' => env('SOLANA_MAX_RETRIES', 3),
        'retry_delay' => env('SOLANA_RETRY_DELAY', 5), // seconds
        'timeout' => env('SOLANA_TIMEOUT', 30), // seconds
        'commitment' => env('SOLANA_COMMITMENT', 'confirmed'), // processed, confirmed, finalized
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for crypto operations.
    |
    */

    'security' => [
        'auto_approval_limit' => env('CRYPTO_AUTO_APPROVAL_LIMIT', 100.0), // USD
        'manual_approval_limit' => env('CRYPTO_MANUAL_APPROVAL_LIMIT', 1000.0), // USD
        'daily_withdrawal_limit' => env('CRYPTO_DAILY_WITHDRAWAL_LIMIT', 10000.0), // USD
        'fraud_detection_enabled' => env('CRYPTO_FRAUD_DETECTION', true),
        'max_risk_score' => env('CRYPTO_MAX_RISK_SCORE', 70),
        'approval_expiry_hours' => env('CRYPTO_APPROVAL_EXPIRY', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fee Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for transaction fees.
    |
    */

    'fees' => [
        'withdrawal_fee_sol' => env('WITHDRAWAL_FEE_SOL', 0.001),
        'withdrawal_fee_usdt' => env('WITHDRAWAL_FEE_USDT', 1.0),
        'network_fee_buffer' => env('NETWORK_FEE_BUFFER', 1.5), // Multiplier for network fees
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for deposit and withdrawal limits.
    |
    */

    'limits' => [
        'min_deposit_sol' => env('MIN_DEPOSIT_SOL', 0.01),
        'min_deposit_usdt' => env('MIN_DEPOSIT_USDT', 5.0),
        'min_withdrawal_sol' => env('MIN_WITHDRAWAL_SOL', 0.01),
        'min_withdrawal_usdt' => env('MIN_WITHDRAWAL_USDT', 5.0),
        'max_withdrawal_sol' => env('MAX_WITHDRAWAL_SOL', 1000.0),
        'max_withdrawal_usdt' => env('MAX_WITHDRAWAL_USDT', 100000.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for deposit monitoring and blockchain scanning.
    |
    */

    'monitoring' => [
        'enabled' => env('CRYPTO_MONITORING_ENABLED', true),
        'scan_interval' => env('CRYPTO_SCAN_INTERVAL', 30), // seconds
        'batch_size' => env('CRYPTO_BATCH_SIZE', 100),
        'max_blocks_behind' => env('CRYPTO_MAX_BLOCKS_BEHIND', 100),
        'webhook_url' => env('CRYPTO_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for exchange rate providers.
    |
    */

    'exchange_rates' => [
        'provider' => env('EXCHANGE_RATE_PROVIDER', 'coingecko'),
        'providers' => [
            'coingecko' => [
                'api_url' => 'https://api.coingecko.com/api/v3',
                'api_key' => env('COINGECKO_API_KEY'),
                'rate_limit' => 50, // requests per minute
            ],
            'binance' => [
                'api_url' => 'https://api.binance.com/api/v3',
                'api_key' => env('BINANCE_API_KEY'),
                'rate_limit' => 1200, // requests per minute
            ],
        ],
        'update_interval' => env('EXCHANGE_RATE_UPDATE_INTERVAL', 300), // seconds
        'cache_ttl' => env('EXCHANGE_RATE_CACHE_TTL', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Hot/Cold Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for hot and cold wallet management.
    |
    */

    'wallets' => [
        'hot_wallet' => [
            'threshold_max' => env('HOT_WALLET_THRESHOLD_MAX', 1000.0), // USD
            'threshold_min' => env('HOT_WALLET_THRESHOLD_MIN', 100.0), // USD
            'auto_transfer' => env('HOT_WALLET_AUTO_TRANSFER', true),
        ],
        'cold_wallet' => [
            'address' => env('COLD_WALLET_ADDRESS'),
            'multi_sig_required' => env('COLD_WALLET_MULTI_SIG', true),
        ],
        'fee_payer' => [
            'address' => env('FEE_PAYER_ADDRESS'),
            'private_key' => env('FEE_PAYER_PRIVATE_KEY'),
            'min_balance' => env('FEE_PAYER_MIN_BALANCE', 1.0), // SOL
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for crypto-related logging.
    |
    */

    'logging' => [
        'enabled' => env('CRYPTO_LOGGING_ENABLED', true),
        'log_level' => env('CRYPTO_LOG_LEVEL', 'info'),
        'log_channel' => env('CRYPTO_LOG_CHANNEL', 'crypto'),
        'log_transactions' => env('CRYPTO_LOG_TRANSACTIONS', true),
        'log_security_events' => env('CRYPTO_LOG_SECURITY_EVENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for development and testing.
    |
    */

    'development' => [
        'mock_blockchain' => env('MOCK_BLOCKCHAIN', false),
        'test_mode' => env('CRYPTO_TEST_MODE', false),
        'simulate_confirmations' => env('SIMULATE_CONFIRMATIONS', false),
        'mock_exchange_rates' => env('MOCK_EXCHANGE_RATES', false),
    ],
]; 