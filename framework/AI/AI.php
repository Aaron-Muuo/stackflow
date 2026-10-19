<?php

declare(strict_types=1);

namespace StackFlow\AI;

use StackFlow\AI\Drivers\GeminiDriver;
use StackFlow\AI\Drivers\OpenAIDriver;
use StackFlow\AI\Drivers\OllamaDriver;
use StackFlow\AI\Contracts\AIDriverInterface;
use RuntimeException;

/**
 * AI Facade
 *
 * Provides a unified static interface to AI providers.
 * Configure driver in app/config/ai.php
 *
 * Usage:
 *   AI::ask('Summarize this: ' . $text);
 *   AI::generate('Write a product description for: ' . $product->name);
 *   AI::driver('openai')->ask('Hello');
 */
class AI
{
    private static ?AIDriverInterface $instance = null;
    private static array $config = [];

    /**
     * Boot the AI system with config.
     */
    public static function boot(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get (or lazy-create) the active driver instance.
     */
    public static function driver(?string $name = null): AIDriverInterface
    {
        if ($name !== null) {
            return self::resolveDriver($name);
        }

        if (self::$instance === null) {
            $defaultDriver = self::$config['driver'] ?? 'gemini';
            self::$instance = self::resolveDriver($defaultDriver);
        }

        return self::$instance;
    }

    /**
     * Send a prompt and get a text response.
     *
     * @param  string $prompt  The user's question or instruction
     * @param  array  $options Optional driver-specific options
     * @return string          The AI's response
     */
    public static function ask(string $prompt, array $options = []): string
    {
        return self::driver()->ask($prompt, $options);
    }

    /**
     * Generate text from a prompt (alias for ask).
     */
    public static function generate(string $prompt, array $options = []): string
    {
        return self::driver()->ask($prompt, $options);
    }

    /**
     * Chat with history (multi-turn conversation).
     *
     * @param  array $messages  [['role'=>'user','content'=>'...'], ['role'=>'assistant','content'=>'...']]
     * @param  array $options
     * @return string
     */
    public static function chat(array $messages, array $options = []): string
    {
        return self::driver()->chat($messages, $options);
    }

    /**
     * Summarize a block of text.
     */
    public static function summarize(string $text, int $maxWords = 100): string
    {
        return self::ask("Summarize the following text in no more than {$maxWords} words:\n\n{$text}");
    }

    /**
     * Classify text into one of the given categories.
     *
     * @param  string   $text
     * @param  string[] $categories
     * @return string   The matched category
     */
    public static function classify(string $text, array $categories): string
    {
        $list = implode(', ', $categories);
        $response = self::ask(
            "Classify the following text into exactly one of these categories: {$list}\n\nText: {$text}\n\nRespond with only the category name."
        );
        return trim($response);
    }

    /**
     * Translate text to a target language.
     */
    public static function translate(string $text, string $targetLanguage): string
    {
        return self::ask("Translate the following text to {$targetLanguage}. Respond with only the translation.\n\n{$text}");
    }

    /**
     * Generate embeddings (vector representation) for text.
     * Not all drivers support this.
     */
    public static function embed(string $text): array
    {
        return self::driver()->embed($text);
    }

    /**
     * Resolve a driver by name.
     */
    private static function resolveDriver(string $name): AIDriverInterface
    {
        $drivers = self::$config['drivers'] ?? [];
        $driverConfig = $drivers[$name] ?? [];

        return match ($name) {
            'gemini' => new GeminiDriver($driverConfig),
            'openai' => new OpenAIDriver($driverConfig),
            'ollama' => new OllamaDriver($driverConfig),
            default  => throw new RuntimeException("Unknown AI driver: [{$name}]. Supported: gemini, openai, ollama"),
        };
    }
}
