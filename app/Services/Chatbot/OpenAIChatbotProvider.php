<?php

namespace App\Services\Chatbot;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIChatbotProvider implements ChatbotProvider
{
    public const ENDPOINT = 'https://api.openai.com/v1/responses';

    public const MAX_BODY = 131072;

    public function __construct(private ChatbotSettings $settings, private ChatbotClock $clock, private ChatbotOutputValidator $validator) {}

    public function answer(#[\SensitiveParameter] ChatbotContext $context, float $deadline): array
    {
        if (! $this->settings->ready()) {
            throw new ChatbotFailure('disabled');
        }
        $payload = ['model' => config('chatbot.model'), 'instructions' => file_get_contents(resource_path('chatbot/instructions.txt')),
            'input' => [['role' => 'developer', 'content' => json_encode(['CAPABILITIES' => $context->capabilities,
                'KNOWLEDGE' => $context->authorizedKnowledge(), 'ALLOWED_ROUTE_KEYS' => $context->routeKeys], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
                ['role' => 'user', 'content' => $context->question]],
            'store' => false, 'stream' => false, 'background' => false,
            'max_output_tokens' => $this->settings->integer('max_output_tokens'),
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'espacio_u_assistance_v1', 'strict' => true, 'schema' => $this->validator->schema()]]];
        if (config('chatbot.reasoning_effort') === 'none') {
            $payload['reasoning'] = ['effort' => 'none'];
        }
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $remaining = $deadline - $this->clock->now() - 1;
            if ($remaining < 1 || connection_aborted()) {
                throw new ChatbotFailure('deadline');
            }
            $timeout = $attempt === 1 ? min(7, $remaining) : $remaining;
            $retry = false;
            $wait = 0.25 + random_int(0, 250) / 1000;
            $status = 0;
            try {
                $response = Http::acceptJson()->asJson()->withToken(config('chatbot.api_key'))
                    ->withoutRedirecting()->timeout($timeout)->connectTimeout(min(3, $timeout))
                    ->withOptions(['verify' => true,
                        'on_headers' => static function ($response) {
                            if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BODY) {
                                throw new ChatbotFailure('oversized_output');
                            }
                        },
                        'progress' => static function ($total, $received) {
                            if ($received > self::MAX_BODY) {
                                throw new ChatbotFailure('oversized_output');
                            }
                        }])->post(self::ENDPOINT, $payload);
                $status = $response->status();
                if (strlen($response->body()) > self::MAX_BODY) {
                    throw new ChatbotFailure('oversized_output');
                }
                if ($this->clock->now() >= $deadline - 1) {
                    throw new ChatbotFailure('deadline');
                }
                if ($response->successful()) {
                    $body = $response->json();
                    $result = $this->parse(is_array($body) ? $body : []);
                    $usage = [];
                    foreach (['input_tokens', 'output_tokens', 'total_tokens'] as $key) {
                        $count = $body['usage'][$key] ?? null;
                        if (is_int($count) && $count >= 0) {
                            $usage[$key] = $count;
                        }
                    }
                    Log::info('chatbot.provider', ['provider' => 'openai', 'model' => config('chatbot.model'), 'http_status' => $status, 'attempts' => $attempt, 'usage' => $usage]);

                    return $result;
                }
                $quota = $status === 429 && in_array($response->json('error.code'), ['insufficient_quota', 'billing_hard_limit_reached'], true);
                $retry = ! $quota && ($status === 429 || $status >= 500);
                $retryHeader = $response->header('Retry-After');
                if ($retryHeader !== '' && $retryHeader !== null) {
                    $delay = is_numeric($retryHeader) ? (float) $retryHeader : max(0, (strtotime($retryHeader) ?: time()) - time());
                    $wait = max($wait, $delay);
                }
            } catch (ConnectionException $error) {
                $retry = preg_match('/cURL error 28|timed out|timeout/i', $error->getMessage()) === 1;
            } catch (ChatbotFailure $error) {
                throw $error;
            } catch (\Throwable) {
                throw new ChatbotFailure('transport');
            }
            Log::notice('chatbot.provider_unavailable', ['provider' => 'openai', 'http_status' => $status, 'attempts' => $attempt]);
            if (! $retry || $attempt === 2 || $deadline - $this->clock->now() - 1 - $wait < 1) {
                throw new ChatbotFailure('provider_unavailable');
            }
            $this->clock->pause($wait);
        }
        throw new ChatbotFailure;
    }

    private function parse(#[\SensitiveParameter] array $body): array
    {
        if (($body['status'] ?? null) !== 'completed' || ! is_array($body['output'] ?? null) || count($body['output']) !== 1) {
            throw new ChatbotFailure('invalid_output');
        }
        $message = $body['output'][0];
        if (($message['type'] ?? null) !== 'message' || ($message['role'] ?? null) !== 'assistant'
            || ($message['status'] ?? null) !== 'completed' || ! is_array($message['content'] ?? null) || count($message['content']) !== 1) {
            throw new ChatbotFailure('invalid_output');
        }
        $content = $message['content'][0];
        if (($content['type'] ?? null) === 'refusal') {
            return ['status' => 'RESTRICTED', 'answer' => ChatbotOutputValidator::RESTRICTED, 'route_key' => null];
        }
        if (($content['type'] ?? null) !== 'output_text' || ! is_string($content['text'] ?? null)) {
            throw new ChatbotFailure('invalid_output');
        }

        return $this->validator->decode($content['text']);
    }
}
