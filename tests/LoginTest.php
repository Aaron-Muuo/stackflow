<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StackFlow\Core\Application;
use StackFlow\Core\Request;
use StackFlow\Core\Router;
use App\Pages\LoginPage;

// Boot application
Application::boot(__DIR__ . '/..');

// Load routes
require __DIR__ . '/../routes/web.php';

echo "Starting LoginPage Tests...\n";

// 1. Test GET /login rendering
$page = new LoginPage();
assert($page->theme() === 'light', 'Default theme must be light');

$router = new Router();
$reqGet = Request::create('GET', '/login');
$resGet = $router->dispatch($reqGet);
assert($resGet->getStatusCode() === 200, 'GET /login should return status 200');
$html = $resGet->getBody();
assert(str_contains($html, 'Welcome back'), 'HTML should contain Welcome back');
assert(str_contains($html, 'name="email"'), 'HTML should contain email field');
assert(str_contains($html, 'name="password"'), 'HTML should contain password field');
assert(str_contains($html, 'Sign in'), 'HTML should contain Sign in button');
echo "PASS: Test 1 - GET /login renders login form in light theme\n";

// 2. Test POST /login with empty inputs (validation failure)
$reqEmpty = Request::create(
    method: 'POST',
    uri: '/login',
    body: ['email' => '', 'password' => ''],
    headers: ['X-Requested-With' => 'XMLHttpRequest']
);
$resEmpty = $router->dispatch($reqEmpty);
assert($resEmpty->getStatusCode() === 422, 'Empty submit should return 422');
$jsonEmpty = json_decode($resEmpty->getBody(), true);
assert(isset($jsonEmpty['errors']['email']), 'Email should have validation error');
assert(isset($jsonEmpty['errors']['password']), 'Password should have validation error');
echo "PASS: Test 2 - Empty inputs trigger validation errors (422)\n";

// 3. Test POST /login with invalid email
$reqInvalidEmail = Request::create(
    method: 'POST',
    uri: '/login',
    body: ['email' => 'not-an-email', 'password' => 'secret123'],
    headers: ['X-Requested-With' => 'XMLHttpRequest']
);
$resInvalidEmail = $router->dispatch($reqInvalidEmail);
assert($resInvalidEmail->getStatusCode() === 422, 'Invalid email should return 422');
$jsonInvalid = json_decode($resInvalidEmail->getBody(), true);
assert(str_contains($jsonInvalid['errors']['email'][0], 'valid email'), 'Should indicate invalid email');
echo "PASS: Test 3 - Invalid email format is rejected\n";

// 4. Test POST /login with valid credentials via AJAX (No account found dummy toast)
$reqValid = Request::create(
    method: 'POST',
    uri: '/login',
    body: ['email' => 'alex@example.com', 'password' => 'password123'],
    headers: ['X-Requested-With' => 'XMLHttpRequest']
);
$resValid = $router->dispatch($reqValid);
assert($resValid->getStatusCode() === 401, 'Valid input should return 401 (dummy auth)');
$jsonValid = json_decode($resValid->getBody(), true);
assert(isset($jsonValid['toast']), 'Response must contain toast payload');
assert($jsonValid['toast']['message'] === 'No account found with these credentials.', 'Toast message must state no account found');
assert($jsonValid['toast']['variant'] === 'danger', 'Toast must be danger variant');
assert($jsonValid['toast']['position'] === 'top-center', 'Toast must be top-center position');
echo "PASS: Test 4 - Valid submit returns 'No account found with these credentials' toast\n";

// 5. Test POST /login non-AJAX fallback
$reqNonAjax = Request::create(
    method: 'POST',
    uri: '/login',
    body: ['email' => 'alex@example.com', 'password' => 'password123'],
    headers: []
);
$resNonAjax = $router->dispatch($reqNonAjax);
assert($resNonAjax->getStatusCode() === 302, 'Non-AJAX submit should redirect');
echo "PASS: Test 5 - Non-AJAX submit sets flash message and redirects\n";

echo "\n>>> ALL LOGIN TESTS PASSED SUCCESSFULLY! <<<\n";
