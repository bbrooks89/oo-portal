<?php
declare(strict_types=1);

// Composer's autoloader finds App\Oo\FlowClient in src/Oo/FlowClient.php (PSR-4).
require __DIR__ . '/../vendor/autoload.php';

use App\Oo\FlowClient;
use App\Oo\OoException;
use App\Oo\FakeFlowRunner;
use App\Oo\AlwaysFailsFlowRunner;

#$client = getenv('OO_FAKE')
$client = getenv('OO_ALWAYS_FAIL')
    ? new AlwaysFailsFlowRunner()
    : new FlowClient(
        baseUrl: getenv('OO_BASE_URL') ?: 'http://localhost:8080/oo/rest/v2',
        username: getenv('OO_USER') ?: 'practice',
        password: getenv('OO_PASSWORD') ?: 'practice',
    );

echo 'Using: ' . get_class($client) . "\n";

// Usage: php bin/run-flow.php open|close|fail
$action = $argv[1] ?? 'open';

try {
    $id = $client->startFlow(
        flowUuid: 'firewall-maintenance',
        inputs: ['action' => $action, 'client' => 'state-01'],
        runName: "Firewall {$action} - practice",
    );
    echo "Started execution {$id}\n";

    $execution = $client->waitForCompletion($id);
    echo "Finished: {$execution->status} / {$execution->resultStatusType}\n";

    exit($execution->succeeded() ? 0 : 1);
} catch (OoException $e) {
    fwrite(STDERR, "OO error: {$e->getMessage()}\n");
    exit(2);
}
