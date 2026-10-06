<?php
declare(strict_types=1);

namespace App\Oo;

/**
 * A snapshot of one OO flow run.
 *
 * "readonly" properties can be set once (in the constructor) and never changed,
 * similar to C#'s init-only properties.
 */
final class Execution
{
    // Statuses that mean the run has stopped and won't change again.
    // Public so other classes can reuse it as Execution::FINISHED_STATUSES.
    public const FINISHED_STATUSES = ['COMPLETED', 'SYSTEM_FAILURE', 'CANCELED'];

    public function __construct(
        public readonly string $executionId,
        public readonly string $status,
        public readonly ?string $resultStatusType,
    ) {
    }

    /**
     * Static factory: builds an Execution from decoded JSON.
     * Called as Execution::fromArray($data), like a static method in C#.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            executionId: (string) ($data['executionId'] ?? ''),
            status: (string) ($data['status'] ?? 'UNKNOWN'),
            resultStatusType: $data['resultStatusType'] ?? null,
        );
    }

    public function isFinished(): bool
    {
        return in_array($this->status, self::FINISHED_STATUSES, true);
    }

    public function succeeded(): bool
    {
        return $this->status === 'COMPLETED' && $this->resultStatusType === 'RESOLVED';
    }
}
