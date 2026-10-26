<?php

namespace App\Services\Chatbot;

class ChatbotClock
{
    public function now(): float
    {
        return hrtime(true) / 1e9;
    }

    public function pause(float $seconds): void
    {
        usleep((int) ($seconds * 1e6));
    }
}
