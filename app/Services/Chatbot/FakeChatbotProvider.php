<?php

namespace App\Services\Chatbot;

// Explicit injection for tests only; never selectable by environment configuration.
class FakeChatbotProvider implements ChatbotProvider
{
    public array $calls = [];

    public function __construct(public array|\Closure $result = ['status' => 'ANSWER', 'answer' => 'Consulta el módulo autorizado para revisar el procedimiento.', 'route_key' => null]) {}

    public function answer(#[\SensitiveParameter] ChatbotContext $context, float $deadline): array
    {
        $this->calls[] = $context;

        return $this->result instanceof \Closure ? ($this->result)($context, $deadline) : $this->result;
    }
}
