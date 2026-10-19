<?php

declare(strict_types=1);

namespace StackFlow\AI\Contracts;

/**
 * Interface that all AI drivers must implement.
 */
interface AIDriverInterface
{
    /**
     * Send a prompt and receive a text response.
     */
    public function ask(string $prompt, array $options = []): string;

    /**
     * Multi-turn chat conversation.
     *
     * @param array $messages [['role'=>'user','content'=>'...'], ...]
     */
    public function chat(array $messages, array $options = []): string;

    /**
     * Generate vector embeddings for the given text.
     * Returns an empty array if the driver does not support embeddings.
     */
    public function embed(string $text): array;
}
