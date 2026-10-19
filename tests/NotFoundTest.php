<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StackFlow\Core\Application;
use StackFlow\Core\Request;
use StackFlow\Core\Response;
use StackFlow\Core\Router;
use StackFlow\Core\Env;

// Boot application
Application::boot(__DIR__ . '/..');

// Register a test route
Router::get('/test-route', \App\Pages\WelcomePage::class);

echo "Starting 404 Redesign Tests...\n";

// Test 1: Direct Response::notFound()
$response1 = Response::notFound(path: '/some/missing/page');
assert($response1->getStatusCode() === 404, 'Response status should be 404');
$html1 = $response1->getBody();
assert(str_contains($html1, '404'), 'HTML should contain 404');
assert(str_contains($html1, 'NOT FOUND'), 'HTML should contain NOT FOUND');
assert(str_contains($html1, 'bg-gray-950'), 'HTML should use error page theme bg-gray-950');
assert(str_contains($html1, 'Return to Home'), 'HTML should contain Return to Home button');
echo "PASS: Test 1 - Response::notFound() produces themed 404 HTML\n";

// Test 2: Router dispatch with unmatched Web route
$router = new Router();
$req2 = Request::create('GET', '/unknown/portal/page');
$response2 = $router->dispatch($req2);
assert($response2->getStatusCode() === 404, 'Router should return 404 status');
$html2 = $response2->getBody();
assert(str_contains($html2, '404'), 'Router 404 should contain 404 lockup');
assert(str_contains($html2, 'NOT FOUND'), 'Router 404 should contain NOT FOUND');
assert(!str_contains($html2, 'Available Registered Routes'), 'Should not contain Available Registered Routes');
assert(!str_contains($html2, 'Request Context'), 'Should not contain Request Context');
assert(!str_contains($html2, 'Peak Memory Usage'), 'Should not contain Environment info');
echo "PASS: Test 2 - Router dispatch unmatched route renders clean minimalist 404 page\n";

// Test 3: Router dispatch with unmatched API route returns JSON
$req3 = Request::create('GET', '/api/unknown-endpoint');
$response3 = $router->dispatch($req3);
assert($response3->getStatusCode() === 404, 'API 404 should have status 404');
$json = json_decode($response3->getBody(), true);
assert(is_array($json), 'API 404 should return valid JSON');
assert($json['code'] === 404, 'API 404 JSON code should be 404');
assert($json['status'] === 'error', 'API 404 status should be error');
echo "PASS: Test 3 - Router dispatch for API requests returns clean JSON 404\n";

// Test 4: 405 Method Not Allowed also renders themed page
$req4 = Request::create('POST', '/test-route');
$response4 = $router->dispatch($req4);
assert($response4->getStatusCode() === 405, 'Status should be 405');
$html4 = $response4->getBody();
assert(str_contains($html4, '404') || str_contains($html4, 'NOT FOUND') || str_contains($html4, 'Method POST is not allowed'), '405 should render themed error');
echo "PASS: Test 4 - Method Not Allowed renders themed error\n";

echo "\n>>> ALL 404 TESTS PASSED! <<<\n";
