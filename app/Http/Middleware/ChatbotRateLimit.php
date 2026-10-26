<?php

namespace App\Http\Middleware;

use App\Services\Chatbot\ChatbotFailure;
use App\Services\Chatbot\ChatbotSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class ChatbotRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'chatbot:'.hash('sha256', (string) $request->user()->id);
        $limit = app(ChatbotSettings::class)->integer('rate_limit_per_minute');
        $admitted = Cache::lock($key.':lock', 2)->get(function () use ($key, $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw new ChatbotFailure('rate_limited', 429, max(1, RateLimiter::availableIn($key)));
            }
            RateLimiter::hit($key, 60);

            return true;
        });
        if (! $admitted) {
            throw new ChatbotFailure('rate_limiter_busy');
        }

        return $next($request);
    }
}
