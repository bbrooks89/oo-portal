<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

use App\Oo\OoException;
use App\Portal\StatusView;

header('Content-Type: application/json');

$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';

// Only ask OO about runs this portal started.
if ($runLog->find($id) === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown run ID.']);
    exit;
}

try {
    $execution = $client->getExecution($id);
    $runLog->updateStatus($id, $execution->status, $execution->resultStatusType);

    [$label, $tone] = StatusView::label($execution->status, $execution->resultStatusType);

    echo json_encode([
        'executionId' => $id,
        'status' => $execution->status,
        'result' => $execution->resultStatusType,
        'label' => $label,
        'tone' => $tone,
        'finished' => $execution->isFinished(),
    ]);
} catch (OoException $ex) {
    http_response_code(502);
    echo json_encode(['error' => 'OO could not be reached.']);
}
