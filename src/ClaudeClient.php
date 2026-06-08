<?php

class ClaudeClient
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL    = 'claude-haiku-4-5'; // MVP: дёшево и быстро; для прода — claude-sonnet-4-6

    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Анализирует HTML страницы по SEO-параметрам.
     * Возвращает декодированный массив с ключами: score, summary, critical, warnings, ok.
     */
    public function analyzeSEO(string $html, string $url): array
    {
        $body = [
            'model'      => self::MODEL,
            'max_tokens' => 4096,
            'system'     => [
                [
                    'type'          => 'text',
                    'text'          => $this->systemPrompt(),
                    'cache_control' => ['type' => 'ephemeral'], // кешируем системный промпт
                ],
            ],
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => "URL: {$url}\n\nHTML страницы:\n{$html}",
                ],
            ],
        ];

        $raw = $this->post($body);

        $text = $this->extractJson($raw['content'][0]['text']);
        $json = json_decode($text, true);
        if ($json === null) {
            throw new RuntimeException('Claude вернул невалидный JSON');
        }

        return $json;
    }

    /**
     * Генерирует варианты Title и Description для страницы.
     * Возвращает массив с ключами: titles (array), descs (array).
     */
    public function generateMeta(string $html, string $url, string $currentTitle, string $currentDesc): array
    {
        $current = "Текущий Title: " . ($currentTitle ?: '(отсутствует)') . "\n"
                 . "Текущий Description: " . ($currentDesc ?: '(отсутствует)');

        $body = [
            'model'      => self::MODEL,
            'max_tokens' => 1024,
            'system'     => [
                [
                    'type'          => 'text',
                    'text'          => "Ты SEO-копирайтер. Предложи улучшенные варианты Title и Description для страницы. Верни ТОЛЬКО валидный JSON без пояснений:\n{\"titles\":[\"вариант 1\",\"вариант 2\",\"вариант 3\"],\"descs\":[\"вариант 1\",\"вариант 2\",\"вариант 3\"]}\nTitle: 50–70 символов, ключевые слова в начале. Description: 120–170 символов, призыв к действию.",
                    'cache_control' => ['type' => 'ephemeral'],
                ],
            ],
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => "URL: {$url}\n{$current}\n\nHTML страницы (фрагмент):\n" . mb_substr($html, 0, 8000),
                ],
            ],
        ];

        $raw  = $this->post($body);
        $text = $this->extractJson($raw['content'][0]['text']);
        $json = json_decode($text, true);
        if ($json === null) {
            throw new RuntimeException('Claude вернул невалидный JSON');
        }
        return $json;
    }

    // -------------------------------------------------------------------------

    private function extractJson(string $text): string
    {
        $text = trim($text);
        if (preg_match('/```(?:json)?\s*([\s\S]+?)\s*```/i', $text, $m)) {
            return trim($m[1]);
        }
        return $text;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Ты SEO-эксперт. Проанализируй HTML страницы и верни ТОЛЬКО валидный JSON без пояснений и markdown-блоков.

Проверь следующие параметры:
- Title: наличие, длина 50–70 символов, ключевые слова
- Description: наличие, длина 120–160 символов
- H1: наличие, количество (должен быть ровно один), уникальность
- H2/H3: последовательность уровней, наличие ключевых слов
- Alt у всех изображений
- Canonical тег
- Meta robots: нет ли noindex там, где не нужно
- Open Graph теги: og:title, og:description, og:image, og:url
- Schema.org разметка (JSON-LD или microdata)
- Viewport мета-тег
- HTTPS (по переданному URL)
- Битые или пустые href у ссылок, видимые в HTML
- Наличие favicon
- Наличие hreflang (для многоязычных сайтов)

Формат ответа — строго этот JSON (никаких дополнительных символов вокруг):
{
  "score": <целое число от 0 до 100>,
  "summary": "<одна фраза — общий вывод о состоянии страницы>",
  "critical": [
    { "title": "<проблема>", "reason": "<почему важно>", "fix": "<как исправить>" }
  ],
  "warnings": [
    { "title": "<проблема>", "reason": "<почему важно>", "fix": "<как исправить>" }
  ],
  "ok": ["<параметр в порядке>"]
}

critical — серьёзные проблемы, которые мешают индексации или снижают позиции.
warnings — недочёты, которые стоит исправить.
ok — список параметров, по которым замечаний нет.
Если проблем нет — пустой массив []. Ключи всегда присутствуют.
PROMPT;
    }

    private function post(array $body): array
    {
        $ch = curl_init(self::API_URL);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 60,
        ]);

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("cURL error: {$curlError}");
        }

        if ($statusCode !== 200) {
            throw new RuntimeException("Claude API HTTP {$statusCode}: {$response}");
        }

        $data = json_decode($response, true);
        if (!isset($data['content'][0]['text'])) {
            throw new RuntimeException('Неожиданный формат ответа от Claude API');
        }

        return $data;
    }
}
