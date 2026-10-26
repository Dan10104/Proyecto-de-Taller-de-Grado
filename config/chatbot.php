<?php

return [
    'enabled' => env('CHATBOT_ENABLED', false),
    'provider' => env('CHATBOT_PROVIDER', 'openai'),
    'model' => env('CHATBOT_MODEL', 'gpt-6-luna'),
    'api_key' => env('OPENAI_API_KEY', ''),
    'max_input_chars' => env('CHATBOT_MAX_INPUT_CHARS', 2000),
    'max_output_tokens' => env('CHATBOT_MAX_OUTPUT_TOKENS', 500),
    'timeout_seconds' => env('CHATBOT_TIMEOUT_SECONDS', 15),
    'rate_limit_per_minute' => env('CHATBOT_RATE_LIMIT_PER_MINUTE', 10),
    'reasoning_effort' => 'none',
];
