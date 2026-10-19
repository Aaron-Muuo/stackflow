<?php

declare(strict_types=1);

namespace StackFlow\AI\Drivers;

use StackFlow\AI\Contracts\AIDriverInterface;
use RuntimeException;

/**
 * Google Gemini AI Driver
 *
 * Config keys:
 *   api_key: your Gemini API key
 *   model:   gemini-1.5-flash (default)
 */
class GeminiDriver implements AIDriverInterface
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(private readonly array $config) {}

    public function ask(string $prompt, array $options = []): string
    {
        return $this->chat([['role' => 'user', 'content' => $prompt]], $options);
    }

    public function chat(array $messages, array $options = []): string
    {
        $apiKey = $this->config['api_key'] ?? throw new RuntimeException('Gemini API key not configured. Set GEMINI_API_KEY in .env');
        $model  = $this->config['model'] ?? 'gemini-1.5-flash';

        $contents = array_map(fn($msg) => [
            'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $msg['content']]],
        ], $messages);

        $payload = json_encode([
            'contents'         => $contents,
            'generationConfig' => array_merge([
                'temperature'     => 0.7,
                'maxOutputTokens' => 2048,
            ], $options),
        ]);

        $url = self::API_BASE . $model . ':generateContent?key=' . $apiKey;

        $response = $this->httpPost($url, $payload);
        $decoded  = json_decode($response, true);

        return $decoded['candidates'][0]['content']['parts'][0]['text']
            ?? throw new RuntimeException('Gemini returned an unexpected response: ' . $response);
    }

    public function embed(string $text): array
    {
        $apiKey = $this->config['api_key'] ?? throw new RuntimeException('Gemini API key not configured.');
        $model  = 'text-embedding-004';
        $url    = self::API_BASE . $model . ':embedContent?key=' . $apiKey;

        $payload  = json_encode(['content' => ['parts' => [['text' => $text]]]]);
        $response = $this->httpPost($url, $payload);
        $decoded  = json_decode($response, true);

        return $decoded['embedding']['values'] ?? [];
    }

    private function httpPost(string $url, string $payload): string
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload),
                'content' => $payload,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            throw new RuntimeException('Failed to connect to Gemini API. Check your internet connection.');
        }

        return $result;
    }
}
