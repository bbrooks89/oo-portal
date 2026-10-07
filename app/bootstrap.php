<?php
declare(strict_types=1);

// Every portal page starts by including this file.

require __DIR__ . '/../vendor/autoload.php';

use App\Oo\FlowClient;
use App\Portal\FlowCatalog;
use App\Portal\RunLog;
use App\Portal\RunService;
use App\Portal\SqliteRunStore;

date_default_timezone_set(getenv('TZ') ?: 'America/New_York');
session_start();

$client = new FlowClient(
    baseUrl: getenv('OO_BASE_URL') ?: 'http://localhost:8080/oo/rest/v2',
    username: getenv('OO_USER') ?: 'practice',
    password: getenv('OO_PASSWORD') ?: 'practice',
);
$catalog = FlowCatalog::default();
//$runLog = new RunLog(__DIR__ . '/../storage/runs.json');
$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

$runLog = getenv('RUN_STORE') === 'sqlite'
    ? new SqliteRunStore(new PDO('sqlite:' . $storageDir . '/runs.sqlite'))
    : new RunLog($storageDir . '/runs.json');
$runService = new RunService($client, $runLog);

/**
 * Escapes text for HTML. Use it on EVERY value you print.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * A secret per browser session, included in every form (CSRF protection).
 */
function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_valid(mixed $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function format_time(string $iso): string
{
    return date('M j, g:i a', strtotime($iso));
}