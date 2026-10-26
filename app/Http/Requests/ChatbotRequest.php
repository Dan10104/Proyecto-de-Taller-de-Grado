<?php

namespace App\Http\Requests;

use App\Services\Chatbot\ChatbotFailure;
use App\Services\Chatbot\ChatbotSettings;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ChatbotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('chatbot.consultar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $question = $this->input('pregunta');
        if (is_string($question) && mb_check_encoding($question, 'UTF-8')) {
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $question)) {
                throw new ChatbotFailure('invalid_input', 422);
            }
            $this->merge(['pregunta' => preg_replace('/\A\s+|\s+\z/u', '', str_replace("\r\n", "\n", $question))]);
        }
    }

    public function rules(): array
    {
        return ['pregunta' => ['required', 'string', 'max:'.app(ChatbotSettings::class)->integer('max_input_chars'),
            function ($attribute, $value, $fail) {
                if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value)) {
                    $fail('Consulta inválida.');
                }
            }]];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (array_diff(array_keys($this->all()), ['pregunta']) || count($this->allFiles()) || $this->query->count()) {
                $validator->errors()->add('pregunta', 'Campos no admitidos.');
            }
        }];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ChatbotFailure('invalid_input', 422);
    }
}
