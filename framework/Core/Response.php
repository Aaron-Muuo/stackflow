<?php

declare(strict_types=1);

namespace StackFlow\Core;

/**
 * StackFlow Response
 *
 * Represents an HTTP response sent back to the client.
 * Supports HTML, JSON, and redirects.
 *
 * @package StackFlow\Core
 */
class Response
{
    /**
     * @param string $body
     * @param int $statusCode
     * @param array<string, string> $headers
     */
    public function __construct(
        protected string $body = '',
        protected int $statusCode = 200,
        protected array $headers = []
    ) {}

    /**
     * Factory for an HTML response.
     */
    public static function html(string $content, int $status = 200): static
    {
        return new static(
            body: $content,
            statusCode: $status,
            headers: ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Factory for a JSON response.
     */
    public static function json(mixed $data, int $status = 200): static
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return new static(
            body: $json ?: '{}',
            statusCode: $status,
            headers: ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    /**
     * Factory for a redirect response.
     */
    public static function redirect(string $url, int $status = 302): static
    {
        return new static(
            body: '',
            statusCode: $status,
            headers: ['Location' => $url]
        );
    }

    /**
     * Factory for a 404 Not Found response.
     */
    public static function notFound(string $message = 'Page Not Found', ?string $path = null): static
    {
        $resolvedPath = $path ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $html = \StackFlow\Debug\ErrorHandler::renderNotFoundPage($resolvedPath, $message);
        return static::html($html, 404);
    }

    /**
     * Set a header on the response.
     */
    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set the status code on the response.
     */
    public function withStatus(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get the body content.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get all headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Send response headers and output the body.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }
}
