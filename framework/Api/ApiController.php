<?php

declare(strict_types=1);

namespace StackFlow\Api;

use StackFlow\Core\Request;
use StackFlow\Core\Response;

/**
 * Base ApiController for StackFlow
 *
 * Provides JSON response formatting, standard REST helpers, and request validation.
 *
 * @package StackFlow\Api
 */
abstract class ApiController
{
    /**
     * Return a JSON response.
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Return a standardized success JSON response.
     */
    protected function success(mixed $data = [], string $message = 'Success', int $status = 200): Response
    {
        return $this->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function error(string $message, int $status = 400, array $errors = []): Response
    {
        $payload = [
            'status'  => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return $this->json($payload, $status);
    }

    /**
     * Return a 404 Not Found JSON response.
     */
    protected function notFound(string $message = 'Resource not found'): Response
    {
        return $this->error($message, 404);
    }

    /**
     * Return a 401 Unauthorized JSON response.
     */
    protected function unauthorized(string $message = 'Unauthorized'): Response
    {
        return $this->error($message, 401);
    }

    /**
     * Validate an incoming request.
     */
    protected function validate(Request $request, array $rules): array
    {
        return $request->validate($rules);
    }
}
