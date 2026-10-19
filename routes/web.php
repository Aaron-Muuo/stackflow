<?php

declare(strict_types=1);

use StackFlow\Core\Router;
use App\Pages\WelcomePage;
use StackFlow\Generator\CrudGeneratorPage;

/**
 * Web Routes
 *
 * Register application web routes here.
 * Format: Router::get('/path', PageClass::class);
 */

Router::get('/', WelcomePage::class);
Router::get('/login', \App\Pages\LoginPage::class);
Router::post('/login', \App\Pages\LoginPage::class);
Router::get('/stackflow/crud', CrudGeneratorPage::class);
Router::post('/stackflow/crud', CrudGeneratorPage::class);
