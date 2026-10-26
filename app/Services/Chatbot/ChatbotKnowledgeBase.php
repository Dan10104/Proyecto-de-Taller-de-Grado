<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Str;

class ChatbotKnowledgeBase
{
    public function entries(): array
    {
        return json_decode(file_get_contents(resource_path('chatbot/knowledge.json')), true, 32, JSON_THROW_ON_ERROR);
    }

    public function select(#[\SensitiveParameter] string $question, array $permissions): ChatbotContext
    {
        $normalized = Str::lower(Str::ascii($question));
        $entries = array_filter($this->entries(), fn ($s) => ! array_diff($s['required_permissions'], $permissions));
        $scored = [];
        foreach ($entries as $entry) {
            $score = count(array_filter($entry['keywords'], fn ($word) => str_contains($normalized, $word)));
            if ($score > 0) {
                $scored[] = [...$entry, 'score' => $score];
            }
        }
        usort($scored, fn ($a, $b) => ($b['score'] <=> $a['score']) ?: ($b['priority'] <=> $a['priority']) ?: strcmp($a['id'], $b['id']));
        $selected = array_slice($scored, 0, 4);
        $capabilities = [];
        $routes = [];
        foreach ($selected as &$entry) {
            if (mb_strlen($entry['text']) > 800) {
                throw new ChatbotFailure('knowledge_configuration');
            }
            unset($entry['score']);
            $capabilities = [...$capabilities, ...$entry['required_permissions']];
            $routes = [...$routes, ...$entry['allowed_route_keys']];
        }
        unset($entry);
        $capabilities = array_values(array_unique($capabilities));
        sort($capabilities);

        return new ChatbotContext($question, $capabilities, $selected, array_values(array_unique($routes)));
    }

    public function localStatus(#[\SensitiveParameter] string $question): ?string
    {
        $q = Str::lower(Str::ascii($question));
        if (preg_match('/\b(?:python|javascript|programacion|codigo fuente)\b/', $q) && ! str_contains($q, 'espacio-u')) {
            return 'OUT_OF_SCOPE';
        }
        if (preg_match('/ignora.{0,40}instruccion|eres administrador|(?:muestra|muestrame|dame|revela|lee).{0,45}(?:prompt|instruccion|clave|secret|password|\.env)|openai_api_key|api key|db_password|system prompt|(?:datos|reservas|prestamos).{0,25}(?:otros|ajenos)|(?:ejecuta|haz).{0,20}(?:sql|consulta sql)|(?:^|\s)(?:aprueba|rechaza|cancela|crea|entrega|devuelve|libera|elimina|modifica|exporta|marca)(?:\s|me\b)|gestiona.{0,15}usuario/', $q)) {
            return 'RESTRICTED';
        }
        if (preg_match('/\b(?:clima|noticias|entretenimiento|chiste|tarea escolar|resuelve.{0,15}ecuacion|programa.{0,15}videojuego)\b/', $q)) {
            return 'OUT_OF_SCOPE';
        }

        return null;
    }
}
