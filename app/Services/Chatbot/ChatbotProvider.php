<?php

namespace App\Services\Chatbot;

interface ChatbotProvider
{
    public function answer(#[\SensitiveParameter] ChatbotContext $context, float $deadline): array;
}
