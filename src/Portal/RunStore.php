<?php
declare(strict_types=1);

namespace App\Portal;

interface RunStore
{
    public function record(array $run): void;
    public function recent(int $limit = 10): array;
    public function find(string $executionId): ?array;
    public function updateStatus(string $executionId, string $status, ?string $result): void;
}