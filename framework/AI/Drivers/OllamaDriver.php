<?php

declare(strict_types=1);

namespace StackFlow\AI\Drivers;

use StackFlow\AI\Contracts\AIDriverInterface;
use RuntimeException;

/**
 * Ollama Driver — local AI models (llama3, mistral, phi3, etc.)
 *
 * Config keys:
 *   base_url: http://localhost:11434 (default)
 *   model:    llama3.2 (default)
 *
 * Ollama must be running locally. Install from: https://ollama.com
 */
class OllamaDriver implements AIDriverInterface
{
    public function __construct(private readonly array $config) {}

    public function ask(string $prompt, array $options = []): string
    {
        return $this->chat([['role' => 'user', 'content' => $prompt]], $options);
    }

    public function chat(array $messages, array $options = []): string
    {
        $baseUrl = rtrim($this->config['base_url'] ?? 'http://localhost:11434', '/');
        $model   = $this->config['model'] ?? 'llama3.2';

        $payload = json_encode(array_merge([
            'model'    => $model,
            'messages' => $messages,
            'stream'   => false,
        ], $options));

        $response = $this->httpPost("{$baseUrl}/api/chat", $payload);
        $decoded  = json_decode($response, true);

        if (isset($decoded['error'])) {
            throw new RuntimeException('Ollama error: ' . $decoded['error']);
        }

        return $decoded['message']['content']
            ?? throw new RuntimeException('Ollama returned an unexpected response: ' . $response);
    }

    public function embed(string $text): array
    {
        $baseUrl = rtrim($this->config['base_url'] ?? 'http://localhost:11434', '/');
        $model   = $this->config['model'] ?? 'nomic-embed-text';

        $payload  = json_encode(['model' => $model, 'input' => $text]);
        $response = $this->httpPost("{$baseUrl}/api/embed", $payload);
        $decoded  = json_decode($response, true);

        return $decoded['embeddings'][0] ?? [];
    }

    private function httpPost(string $url, string $payload): string
    {
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($payload),
                'content'       => $payload,
                'ignore_errors' => true,
                'timeout'       => 120,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            throw new RuntimeException(
                "Failed to connect to Ollama at {$url}. Is Ollama running? Start it with: ollama serve"
            );
        }

        return $result;
    }
}
