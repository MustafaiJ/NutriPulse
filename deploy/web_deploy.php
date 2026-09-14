<?php

/**
 * TEMPORARY web runner for Laravel artisan commands.
 *
 * DO NOT LEAVE THIS FILE ON THE SERVER. Upload it to your project's
 * public/ folder as `_deploy.php`, use it for the steps below, then
 * DELETE it. It is guarded by a token from APP_KEY-style env: DEPLOY_TOKEN.
 *
 * Usage (replace TOKEN with your DEPLOY_TOKEN value):
 *   /up.php?token=TOKEN&cmd=migrate
 *   /up.php?token=TOKEN&cmd=config:cache
 *   /up.php?token=TOKEN&cmd=route:cache
 *   /up.php?token=TOKEN&cmd=view:cache
 *   /up.php?token=TOKEN&cmd=down        (maintenance mode on)
 *   /up.php?token=TOKEN&cmd=up          (maintenance mode off)
 *
 * Only these command names are accepted. Anything else => 403.
 */

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;

if (($_GET['token'] ?? '') === '') {
    http_response_code(403);
    echo 'Forbidden: token missing.';

    exit;
}

$allowed = [
    'migrate' => 'migrate --seed --force',
    'config:cache' => 'config:cache',
    'route:cache' => 'route:cache',
    'view:cache' => 'view:cache',
    'down' => 'down --render=errors::503',
    'up' => 'up',
];

$cmd = (string) ($_GET['cmd'] ?? '');

if (! isset($allowed[$cmd])) {
    http_response_code(403);
    echo 'Forbidden: unknown command. Allowed: '.implode(', ', array_keys($allowed)).'.';

    exit;
}

$app = require __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$expected = (string) config('app.deploy_token');

if ($expected === '' || ! hash_equals($expected, (string) $_GET['token'])) {
    http_response_code(403);
    echo 'Forbidden: bad token.';

    exit;
}

@set_time_limit(300);

header('Content-Type: text/plain; charset=utf-8');
echo "\$ artisan {$allowed[$cmd]}\n";
echo str_repeat('-', 50)."\n";

Artisan::call($allowed[$cmd]);

echo Artisan::output();
echo str_repeat('-', 50)."\nDone.\n";
