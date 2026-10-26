<?php

namespace App\Services\Chatbot;

use App\Services\AccessSessionService;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    public function __construct(private AuthorizationService $authorization, private AccessSessionService $sessions,
        private ChatbotKnowledgeBase $knowledge, private ChatbotRouteRegistry $routes, private ChatbotProvider $provider,
        private ChatbotOutputValidator $validator, private ChatbotSettings $settings, private ChatbotClock $clock) {}

    public function consult(Request $request, #[\SensitiveParameter] string $question): array
    {
        $start = $request->attributes->get('chatbot.started', $this->clock->now());
        $deadline = $start + $this->settings->integer('timeout_seconds');
        $permissions = $this->permissions($request);
        if (! $this->settings->ready()) {
            throw new ChatbotFailure('disabled');
        }
        $context = $this->knowledge->select($question, $permissions);
        $local = $this->knowledge->localStatus($question);
        if ($local) {
            $result = ['status' => $local, 'answer' => constant(ChatbotOutputValidator::class.'::'.$local), 'route_key' => $context->routeKeys[0] ?? null];
        } elseif (! $context->snippets) {
            $result = ['status' => 'ANSWER', 'answer' => ChatbotOutputValidator::INSUFFICIENT, 'route_key' => null];
        } else {
            $result = $this->provider->answer($context, $deadline);
        }
        $result = $this->validator->validate($result);
        $current = $this->permissions($request);
        if (array_diff($context->capabilities, $current)) {
            throw new ChatbotFailure('access_changed', 403);
        }
        if ($this->clock->now() >= $deadline || connection_aborted()) {
            throw new ChatbotFailure('deadline');
        }
        $key = $result['route_key'];
        $link = in_array($key, $context->routeKeys, true) ? $this->routes->resolve($key, $current) : null;
        if ($key !== null && $link === null) {
            $result['answer'] = ChatbotOutputValidator::INSUFFICIENT;
        }
        Log::info('chatbot.success', ['latency_ms' => round(($this->clock->now() - $start) * 1000), 'status' => $result['status']]);

        return ['status' => $result['status'], 'answer' => $result['answer'], 'link' => $link];
    }

    private function permissions(Request $request): array
    {
        if (! $request->user() || ! $this->sessions->validateAndRenew($request)) {
            throw new ChatbotFailure('session_ended', 401);
        }
        $user = $request->user()->fresh();
        $permissions = $user ? $this->authorization->permissions($user) : [];
        if (! in_array('chatbot.consultar', $permissions, true)) {
            throw new ChatbotFailure('access_changed', 403);
        }

        return $permissions;
    }
}
