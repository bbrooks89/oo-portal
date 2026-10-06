<?php
declare(strict_types=1);

namespace App\Oo;

interface FlowRunner
{
    public function startFlow(string $flowUuid, array $inputs = [], ?string $runName = null): string;
    public function getExecution(string $executionId): Execution;
    public function waitForCompletion(string $executionId, int $pollSeconds = 2, int $maxWaitSeconds = 60): Execution;
}
