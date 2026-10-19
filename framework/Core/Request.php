<?php

declare(strict_types=1);

namespace StackFlow\Core;

/**
 * StackFlow Request
 *
 * Represents an incoming HTTP request. Captures all superglobals at
 * construction and provides a clean, typed API for accessing request data,
 * headers, and performing input validation.
 *
 * @package StackFlow\Core
 */
class Request
{
    /**
     * The HTTP method (uppercase): GET, POST, PUT, PATCH, DELETE, etc.
     */
    public readonly string $method;

    /**
     * Full request URI including query string (e.g. `/users/42?tab=info`).
     */
    public readonly string $uri;

    /**
     * URI path without query string (e.g. `/users/42`).
     */
    public readonly string $path;

    /**
     * Parsed query-string parameters (`$_GET`).
     *
     * @var array<string, mixed>
     */
    public readonly array $query;

    /**
     * Parsed request body parameters (`$_POST` or decoded JSON body).
     *
     * @var array<string, mixed>
     */
    public readonly array $body;

    /**
     * Uploaded files (`$_FILES`).
     *
     * @var array<string, mixed>
     */
    public readonly array $files;

    /**
     * HTTP request headers (lowercase-normalised keys).
     *
     * @var array<string, string>
     */
    public readonly array $headers;

    /**
     * Named route parameters extracted by the router (e.g. `['id' => '42']`).
     *
     * The router populates this after matching; declared non-readonly so the
     * router can inject values post-construction.
     *
     * @var array<string, string>
     */
    public array $params = [];

    /**
     * Private constructor — use capture() as a factory.
     *
     * @param string               $method
     * @param string               $uri
     * @param string               $path
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $body
     * @param array<string, mixed>  $files
     * @param array<string, string> $headers
     */
    private function __construct(
        string $method,
        string $uri,
        string $path,
        array  $query,
        array  $body,
        array  $files,
        array  $headers,
    ) {
        $this->method  = $method;
        $this->uri     = $uri;
        $this->path    = $path;
        $this->query   = $query;
        $this->body    = $body;
        $this->files   = $files;
        $this->headers = $headers;
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Capture the current HTTP request from PHP superglobals.
     *
     * Handles method spoofing via a `_method` field (for PUT/PATCH/DELETE
     * from HTML forms) and decodes JSON request bodies automatically.
     *
     * @return static
     */
    public static function capture(): static
    {
        $rawMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Parse the URI and strip the query string to get a clean path
        $uri        = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedPath = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path       = '/' . trim((string) $parsedPath, '/');

        $query = $_GET ?? [];
        $body  = static::parseBody($rawMethod);

        // Method spoofing: HTML forms can send PUT/PATCH/DELETE via _method
        $method = $rawMethod;
        if ($rawMethod === 'POST' && isset($body['_method'])) {
            $spoofed = strtoupper((string) $body['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], strict: true)) {
                $method = $spoofed;
            }
            unset($body['_method']);
        }

        $headers = static::captureHeaders();

        return new static(
            method : $method,
            uri    : $uri,
            path   : $path,
            query  : $query,
            body   : $body,
            files  : $_FILES ?? [],
            headers: $headers,
        );
    }

    /**
     * Create a programmatic Request instance (useful for testing and sub-requests).
     */
    public static function create(
        string $method = 'GET',
        string $uri = '/',
        array  $body = [],
        array  $query = [],
        array  $headers = []
    ): static {
        $parsedPath = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path       = '/' . trim((string) $parsedPath, '/');

        $normalizedHeaders = [];
        foreach ($headers as $k => $v) {
            $normalizedHeaders[strtolower((string) $k)] = (string) $v;
        }

        return new static(
            method : strtoupper($method),
            uri    : $uri,
            path   : $path,
            query  : $query,
            body   : $body,
            files  : [],
            headers: $normalizedHeaders,
        );
    }

    // -------------------------------------------------------------------------
    // Data access
    // -------------------------------------------------------------------------

    /**
     * Retrieve a value from the request body or query string.
     *
     * Body takes precedence over the query string.
     *
     * @param  string $key     The input key.
     * @param  mixed  $default Value to return when the key is absent.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Alias for get() — mirrors Laravel's `$request->input()` convention.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * Check whether a given key exists in the body or query string.
     *
     * @param  string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body) || array_key_exists($key, $this->query);
    }

    /**
     * Retrieve only a subset of input values.
     *
     * @param  string ...$keys Keys to include.
     * @return array<string, mixed>
     */
    public function only(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Retrieve all input except the specified keys.
     *
     * @param  string ...$keys Keys to exclude.
     * @return array<string, mixed>
     */
    public function except(string ...$keys): array
    {
        $all = array_merge($this->query, $this->body);
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        return $all;
    }

    /**
     * Retrieve all merged body + query data.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * Retrieve a named route parameter set by the router.
     *
     * @param  string $key
     * @param  string|null $default
     * @return string|null
     */
    public function param(string $key, ?string $default = null): ?string
    {
        return $this->params[$key] ?? $default;
    }

    // -------------------------------------------------------------------------
    // Method & type checks
    // -------------------------------------------------------------------------

    /**
     * Check whether the request uses the given HTTP method (case-insensitive).
     *
     * @param  string $method e.g. `'GET'`, `'post'`.
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Determine whether the request was made via XMLHttpRequest / fetch.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        $xReq = $this->headers['x-requested-with'] ?? '';
        $sfAjax = $this->headers['x-stackflow-ajax'] ?? '';
        $queryAjax = $this->get('_ajax');

        return strtolower($xReq) === 'xmlhttprequest'
            || $sfAjax === '1'
            || $queryAjax !== null
            || $this->wantsJson();
    }

    /**
     * Determine whether the request expects a JSON response.
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        $accept = $this->headers['accept'] ?? '';
        $format = $this->get('_format', '');

        return str_contains($accept, 'application/json')
            || str_contains($accept, '+json')
            || $format === 'json';
    }

    /**
     * Determine whether the request body is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->headers['content-type'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    // -------------------------------------------------------------------------
    // Network
    // -------------------------------------------------------------------------

    /**
     * Retrieve the client IP address.
     *
     * Checks common reverse-proxy headers before falling back to REMOTE_ADDR.
     *
     * @return string
     */
    public function ip(): string
    {
        $candidates = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            $value = $_SERVER[$key] ?? '';
            if ($value !== '') {
                // X-Forwarded-For may be a comma-separated list; take the first
                return trim(explode(',', $value)[0]);
            }
        }

        return '0.0.0.0';
    }

    // -------------------------------------------------------------------------
    // Headers & auth
    // -------------------------------------------------------------------------

    /**
     * Retrieve a specific request header (case-insensitive lookup).
     *
     * @param  string $name    Header name.
     * @param  string $default Fallback value.
     * @return string
     */
    public function header(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * Extract the Bearer token from the Authorization header.
     *
     * @return string|null The token string, or null if not present.
     */
    public function bearerToken(): ?string
    {
        $auth = $this->headers['authorization'] ?? '';

        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * Validate request input against a set of rules.
     *
     * Supported rules (pipe-separated):
     * - `required`       — field must be present and non-empty
     * - `email`          — must be a valid e-mail address
     * - `numeric`        — must be numeric
     * - `min:N`          — string length or numeric value >= N
     * - `max:N`          — string length or numeric value <= N
     * - `string`         — must be a string
     * - `boolean`        — must be boolean-castable (true/false/1/0)
     * - `confirmed`      — must have a matching `{field}_confirmation` value
     *
     * Returns the validated (and type-cast where applicable) data array.
     * Throws a {@see ValidationException} on failure.
     *
     * @param  array<string, string> $rules  Map of field => rule-string.
     * @return array<string, mixed>          Validated data.
     *
     * @throws ValidationException When one or more rules fail.
     */
    public function validate(array $rules): array
    {
        $errors    = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $value     = $this->get($field);
            $ruleList  = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                [$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);

                $error = match ($ruleName) {
                    'required'  => $this->validateRequired($field, $value),
                    'email'     => $this->validateEmail($field, $value),
                    'numeric'   => $this->validateNumeric($field, $value),
                    'string'    => $this->validateString($field, $value),
                    'boolean'   => $this->validateBoolean($field, $value),
                    'min'       => $this->validateMin($field, $value, (int) $ruleParam),
                    'max'       => $this->validateMax($field, $value, (int) $ruleParam),
                    'confirmed' => $this->validateConfirmed($field, $value),
                    default     => null,
                };

                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }

            $validated[$field] = $value;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    // -------------------------------------------------------------------------
    // Internal – body parsing
    // -------------------------------------------------------------------------

    /**
     * Parse the request body depending on content-type.
     *
     * @param  string              $method Raw HTTP method.
     * @return array<string, mixed>
     */
    private static function parseBody(string $method): array
    {
        if ($method === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                return (array) (json_decode((string) $raw, associative: true) ?? []);
            }

            return $_POST ?? [];
        }

        // For PUT/PATCH/DELETE with form or JSON body
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], strict: true)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $raw         = file_get_contents('php://input');

            if (str_contains($contentType, 'application/json')) {
                return (array) (json_decode((string) $raw, associative: true) ?? []);
            }

            parse_str((string) $raw, $parsed);
            return $parsed;
        }

        return [];
    }

    /**
     * Capture and normalise all HTTP request headers.
     *
     * @return array<string, string>
     */
    private static function captureHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
            return $headers;
        }

        // Fallback: build from $_SERVER
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name           = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], strict: true)) {
                $name           = strtolower(str_replace('_', '-', $key));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    // -------------------------------------------------------------------------
    // Internal – individual validators
    // -------------------------------------------------------------------------

    private function validateRequired(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return "The {$field} field is required.";
        }
        return null;
    }

    private function validateEmail(string $field, mixed $value): ?string
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} must be a valid email address.";
        }
        return null;
    }

    private function validateNumeric(string $field, mixed $value): ?string
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            return "The {$field} must be numeric.";
        }
        return null;
    }

    private function validateString(string $field, mixed $value): ?string
    {
        if ($value !== null && !is_string($value)) {
            return "The {$field} must be a string.";
        }
        return null;
    }

    private function validateBoolean(string $field, mixed $value): ?string
    {
        if ($value !== null && filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            return "The {$field} must be true or false.";
        }
        return null;
    }

    private function validateMin(string $field, mixed $value, int $min): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value < $min) {
            return "The {$field} must be at least {$min}.";
        }
        if (is_string($value) && mb_strlen($value) < $min) {
            return "The {$field} must be at least {$min} characters.";
        }
        return null;
    }

    private function validateMax(string $field, mixed $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > $max) {
            return "The {$field} may not be greater than {$max}.";
        }
        if (is_string($value) && mb_strlen($value) > $max) {
            return "The {$field} may not be greater than {$max} characters.";
        }
        return null;
    }

    private function validateConfirmed(string $field, mixed $value): ?string
    {
        $confirmation = $this->get("{$field}_confirmation");
        if ($value !== $confirmation) {
            return "The {$field} confirmation does not match.";
        }
        return null;
    }
}

// ---------------------------------------------------------------------------
// Validation Exception (lives in the same file for cohesion)
// ---------------------------------------------------------------------------

/**
 * Thrown by Request::validate() when one or more validation rules fail.
 */
class ValidationException extends \RuntimeException
{
    /**
     * @param array<string, array<string>> $errors Map of field => list of error messages.
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'The given data was invalid.',
    ) {
        parent::__construct($message);
    }

    /**
     * Retrieve a flat list of all error messages.
     *
     * @return array<string>
     */
    public function allErrors(): array
    {
        return array_merge(...array_values($this->errors));
    }
}
