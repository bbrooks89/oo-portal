<?php
declare(strict_types=1);

namespace App\Portal;

use App\Oo\FlowRunner;

final class RunService
{
    public function __construct(
        private FlowRunner $runner,
        private RunLog $runLog,
    ) {
    }

    public function start(array $flow, string $flowId, array $inputs, string $requester): string
    {
        $executionId = $this->runner->startFlow(
            flowUuid: $flow['uuid'],
            inputs: $inputs,
            runName: "{$flow['name']} ({$requester})",
        );

        $this->runLog->record([
            'executionId' => $executionId,
            'flowId' => $flowId,
            'flowName' => $flow['name'],
            'requester' => $requester,
            'inputs' => $inputs,
            'requestedAt' => date('c'),
            'status' => 'RUNNING',
            'result' => null,
        ]);

        return $executionId;
    }
}