<?php

// Simple check: if there is no .env in @core, show a friendly message and stop.
if (!file_exists(__DIR__ . '/@core/.env')) {
     echo 'Environment file is missing. Please install or configure the application first.';
     exit;
}

define('LARAVEL_START', microtime(true));

require __DIR__ . '/@core/vendor/autoload.php';

$app = require_once __DIR__ . '/@core/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
     $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
