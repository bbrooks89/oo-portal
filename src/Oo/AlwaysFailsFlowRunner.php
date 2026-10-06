<?php
declare(strict_types=1);

namespace App\Oo;

final class AlwaysFailsFlowRunner implements FlowRunner
{
    public function startFlow(string $flowUuid, array $inputs = [], ?string $runName = null): string
    {
        return (string) random_int(100000000, 999999999);
    }

    public function getExecution(string $executionId): Execution
    {
        return new Execution($executionId, 'COMPLETED', 'ERROR');
    }

    public function waitForCompletion(string $executionId, int $pollSeconds = 2, int $maxWaitSeconds = 60): Execution
    {
        return $this->getExecution($executionId);
    }
}
