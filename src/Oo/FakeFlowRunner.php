<?php
declare(strict_types=1);

namespace App\Oo;

use Override;

final class FakeFlowRunner implements FlowRunner
{
    private array $runs = [];  // execution ID => inupts

    public function startFlow(string $flowUuid, array $inputs = [], ?string $runName = null): string
    {
        $id = (string) random_int(100000000, 999999999);
        $this->runs[$id] = $inputs;
        return $id;    
    }

    public function getExecution(string $executionId): Execution
    {
        $action = $this->runs[$executionId]['action'] ?? null;
        $result = $action === 'fail' ? 'ERROR' : 'RESOLVED';
        return new Execution($executionId, 'COMPLETED', $result);
    }

    #[\Override]
    public function waitForCompletion(string $executionId, int $pollSeconds = 2, int $maxWaitSeconds = 60): Execution
    {
        return $this->getExecution($executionId);
    }
}