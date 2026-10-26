<?php

namespace App\Http\Middleware;

use App\Services\Chatbot\ChatbotClock;
use App\Services\Chatbot\ChatbotFailure;
use App\Services\Chatbot\ChatbotSettings;
use Closure;
use Illuminate\Http\Request;

class ChatbotBoundary
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('asistente', 'asistente/*')) {
            return $next($request);
        }
        $request->attributes->set('chatbot.started', app(ChatbotClock::class)->now());
        config(['app.debug' => false]);
        if ($request->isMethod('POST')) {
            $request->headers->set('Accept', 'application/json');
            if (strlen($request->getContent()) > app(ChatbotSettings::class)->integer('max_input_chars') * 12 + 1024) {
                return (new ChatbotFailure('invalid_input', 422))->response();
            }
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
