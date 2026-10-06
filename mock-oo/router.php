<?php
declare(strict_types=1);

/*
 * A tiny fake of OO Central's REST API, for practicing without a real OO server.
 * Run with PHP's built-in web server:  php -S 0.0.0.0:8080 router.php
 *
 * Supports:
 *   POST /oo/rest/v2/executions              start a "flow", returns an execution ID
 *   GET  /oo/rest/v2/executions/{id}/summary status: RUNNING for 5 seconds, then COMPLETED
 *
 * Pass the input "action" = "fail" to simulate a flow that finishes with an ERROR result.
 */

$stateFile = sys_get_temp_dir() . '/mock-oo-executions.json';
$executions = is_file($stateFile) ? json_decode((string) file_get_contents($stateFile), true) : [];

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function respond(int $status, mixed $body): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body);
}

// Require Basic auth, like real Central does (any username/password is accepted here).
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
if (!str_starts_with($headers['authorization'] ?? '', 'Basic ')) {
    respond(401, ['error' => 'Authentication required']);
    return;
}

// Start a flow.
if ($method === 'POST' && $path === '/oo/rest/v2/executions') {
    $body = json_decode((string) file_get_contents('php://input'), true);

    if (empty($body['flowUuid'])) {
        respond(400, ['error' => 'flowUuid is required']);
        return;
    }

    $id = (string) random_int(100000000, 999999999);
    $executions[$id] = [
        'flowUuid' => $body['flowUuid'],
        'runName' => $body['runName'] ?? $body['flowUuid'],
        'inputs' => $body['inputs'] ?? [],
        'startedAt' => time(),
    ];
    file_put_contents($stateFile, json_encode($executions));

    respond(201, $id);
    return;
}

// Get the status of a run.
if ($method === 'GET' && preg_match('#^/oo/rest/v2/executions/(\d+)/summary$#', $path, $m)) {
    $run = $executions[$m[1]] ?? null;
    if ($run === null) {
        respond(404, ['error' => "Execution {$m[1]} not found"]);
        return;
    }

    $finished = (time() - $run['startedAt']) >= 5;
    $failed = ($run['inputs']['action'] ?? '') === 'fail';

    respond(200, [[
        'executionId' => $m[1],
        'flowUuid' => $run['flowUuid'],
        'runName' => $run['runName'],
        'status' => $finished ? 'COMPLETED' : 'RUNNING',
        'resultStatusType' => $finished ? ($failed ? 'ERROR' : 'RESOLVED') : null,
        'startTime' => $run['startedAt'] * 1000,
    ]]);
    return;
}

respond(404, ['error' => "No route for {$method} {$path}"]);
