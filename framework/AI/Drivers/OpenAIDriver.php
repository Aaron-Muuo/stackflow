<?php

declare(strict_types=1);

namespace StackFlow\AI\Drivers;

use StackFlow\AI\Contracts\AIDriverInterface;
use RuntimeException;

/**
 * OpenAI Driver (GPT-4o, GPT-4o-mini, etc.)
 *
 * Config keys:
 *   api_key:      your OpenAI secret key
 *   model:        gpt-4o-mini (default)
 *   organization: optional organization ID
 */
class OpenAIDriver implements AIDriverInterface
{
    private const API_BASE = 'https://api.openai.com/v1/';

    public function __construct(private readonly array $config) {}

    public function ask(string $prompt, array $options = []): string
    {
        return $this->chat([['role' => 'user', 'content' => $prompt]], $options);
    }

    public function chat(array $messages, array $options = []): string
    {
        $apiKey = $this->config['api_key'] ?? throw new RuntimeException('OpenAI API key not configured. Set OPENAI_API_KEY in .env');
        $model  = $this->config['model'] ?? 'gpt-4o-mini';

        $payload = json_encode(array_merge([
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => 0.7,
            'max_tokens'  => 2048,
        ], $options));

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];

        if ($org = $this->config['organization'] ?? null) {
            $headers[] = 'OpenAI-Organization: ' . $org;
        }

        $response = $this->httpPost(self::API_BASE . 'chat/completions', $payload, $headers);
        $decoded  = json_decode($response, true);

        if (isset($decoded['error'])) {
            throw new RuntimeException('OpenAI error: ' . $decoded['error']['message']);
        }

        return $decoded['choices'][0]['message']['content']
            ?? throw new RuntimeException('OpenAI returned an unexpected response.');
    }

    public function embed(string $text): array
    {
        $apiKey = $this->config['api_key'] ?? throw new RuntimeException('OpenAI API key not configured.');

        $payload = json_encode(['model' => 'text-embedding-3-small', 'input' => $text]);
        $headers = ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'];

        $response = $this->httpPost(self::API_BASE . 'embeddings', $payload, $headers);
        $decoded  = json_decode($response, true);

        return $decoded['data'][0]['embedding'] ?? [];
    }

    private function httpPost(string $url, string $payload, array $headers): string
    {
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $payload,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            throw new RuntimeException('Failed to connect to OpenAI API.');
        }

        return $result;
    }
}
