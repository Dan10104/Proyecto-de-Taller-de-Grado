<?php

namespace Tests;

use App\Services\Chatbot\ChatbotClock;

class ChatbotTestClock extends ChatbotClock
{
    public float $time = 100;

    public array $waits = [];

    public function now(): float
    {
        return $this->time;
    }

    public function pause(float $seconds): void
    {
        $this->waits[] = $seconds;
        $this->time += $seconds;
    }
}
