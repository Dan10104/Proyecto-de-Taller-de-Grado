<?php

namespace App\Services\Chatbot;

class ChatbotOutputValidator
{
    public const RESTRICTED = 'No puedo ejecutar operaciones ni proporcionar información no autorizada. Usa el módulo correspondiente si tienes permiso.';

    public const OUT_OF_SCOPE = 'El asistente está destinado a orientar sobre ESPACIO-U y sus procedimientos habilitados.';

    public const INSUFFICIENT = 'Esa información no está disponible en el contexto de asistencia.';

    public function __construct(private ChatbotRouteRegistry $routes) {}

    public function schema(): array
    {
        return ['type' => 'object', 'additionalProperties' => false, 'required' => ['status', 'answer', 'route_key'],
            'properties' => ['status' => ['type' => 'string', 'enum' => ['ANSWER', 'OUT_OF_SCOPE', 'RESTRICTED']],
                'answer' => ['type' => 'string'], 'route_key' => ['type' => ['string', 'null'], 'enum' => [...$this->routes->keys(), null]]]];
    }

    public function decode(#[\SensitiveParameter] string $json): array
    {
        try {
            $value = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
            // Flat scalar schema: consume every member, including duplicate/escaped keys.
            $offset = 0;
            if (! preg_match('/\A\s*\{/u', $json, $start)) {
                throw new ChatbotFailure('invalid_output');
            }
            $offset = strlen($start[0]);
            $keys = [];
            $pattern = '/\G\s*("(?:[^"\\\\]|\\\\.)*")\s*:\s*("(?:[^"\\\\]|\\\\.)*"|null)\s*([,}])/us';
            do {
                if (! preg_match($pattern, $json, $member, 0, $offset)) {
                    throw new ChatbotFailure('invalid_output');
                }
                $key = json_decode($member[1], true, 8, JSON_THROW_ON_ERROR);
                if (in_array($key, $keys, true)) {
                    throw new ChatbotFailure('invalid_output');
                }
                $keys[] = $key;
                $offset += strlen($member[0]);
            } while ($member[3] === ',');
            if (trim(substr($json, $offset)) !== '') {
                throw new ChatbotFailure('invalid_output');
            }

            return $this->validate($value);
        } catch (\JsonException|\TypeError) {
            throw new ChatbotFailure('invalid_output');
        }
    }

    public function validate(#[\SensitiveParameter] array $value): array
    {
        $keys = array_keys($value);
        sort($keys);
        if ($keys !== ['answer', 'route_key', 'status'] || ! in_array($value['status'], ['ANSWER', 'OUT_OF_SCOPE', 'RESTRICTED'], true)
            || ! is_string($value['answer']) || ! mb_check_encoding($value['answer'], 'UTF-8') || trim($value['answer']) === ''
            || mb_strlen($value['answer']) > 2000 || ($value['route_key'] !== null && ! in_array($value['route_key'], $this->routes->keys(), true))) {
            throw new ChatbotFailure('invalid_output');
        }
        $text = $value['answer'];
        if (preg_match('/[<>`]|https?:|www\.|javascript:|\[[^\]]*\]\(|(?:^|\n)\s*#{1,6}\s|\*\*|[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/iu', $text)) {
            throw new ChatbotFailure('invalid_output');
        }
        // Defense in depth, not a claim of universal semantic classification.
        if (preg_match('/(?:(?:^|[.!?]\s+)(?:(?:tu|su|la|el)\s+)?(?:reserva|préstamo|prestamo|cancelación|cancelacion|notificación|notificacion|usuario|incidencia)\s+(?:(?:fue|ha sido)\s+)?(?:cread[oa]|aprobad[oa]|cancelad[oa]|entregad[oa]|devuelt[oa]|marcad[oa]|completad[oa]|modificad[oa])\b|\b(?:he|hemos)\s+(?:creado|aprobado|cancelado|entregado|devuelto|marcado|ejecutado)|\bhecho\b|\b(?:clave|password|contraseña|secreto|token)\s*(?::|=|es\b)\s*\S+|eres el asistente de ESPACIO-U|OPENAI_API_KEY|system prompt|CAPABILITIES|ALLOWED_ROUTE_KEYS|\bsk-[a-z0-9_-]{12,}|\bSELECT\b.+\bFROM\b|\b(?:eval|exec)\s*\()/iu', $text)) {
            return ['status' => 'RESTRICTED', 'answer' => self::RESTRICTED, 'route_key' => null];
        }
        if ($value['status'] !== 'ANSWER') {
            $value['answer'] = constant(self::class.'::'.$value['status']);
        }
        if ($value['status'] === 'OUT_OF_SCOPE') {
            $value['route_key'] = null;
        }

        return $value;
    }
}
