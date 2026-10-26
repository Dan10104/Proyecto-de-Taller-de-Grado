<?php

namespace App\Services\Chatbot;

class ChatbotSettings
{
    public function integer(string $name): int
    {
        $value = filter_var(config('chatbot.'.$name), FILTER_VALIDATE_INT);
        if ($value === false || $value < 1) {
            throw new ChatbotFailure('configuration');
        }

        return $value;
    }

    public function ready(): bool
    {
        foreach (['max_input_chars', 'max_output_tokens', 'timeout_seconds', 'rate_limit_per_minute'] as $key) {
            $this->integer($key);
        }

        return config('chatbot.enabled') === true && config('chatbot.provider') === 'openai'
            && is_string(config('chatbot.api_key')) && trim(config('chatbot.api_key')) !== ''
            && is_string(config('chatbot.model')) && preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,99}\z/', config('chatbot.model')) === 1;
    }
}
