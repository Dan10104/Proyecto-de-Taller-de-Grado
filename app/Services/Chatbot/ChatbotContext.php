<?php

namespace App\Services\Chatbot;

final readonly class ChatbotContext
{
    public function __construct(#[\SensitiveParameter] public string $question, public array $capabilities, public array $snippets, public array $routeKeys) {}

    public function authorizedKnowledge(): array
    {
        return array_map(fn ($snippet) => ['topic' => $snippet['topic'], 'text' => $snippet['text']], $this->snippets);
    }
}
