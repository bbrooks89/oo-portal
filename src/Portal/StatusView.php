<?php
declare(strict_types=1);

namespace App\Portal;

use App\Oo\Execution;

/**
 * Turns OO's raw status values into what people see on screen.
 * Both the pages and the status API use this, so labels always match.
 *
 * All methods are static: there's no state to hold, so there's no object to create.
 * Call them as StatusView::label(...).
 */
final class StatusView
{
    /** @return array{0: string, 1: string} [label, tone] */
    public static function label(?string $status, ?string $result): array
    {
        // match(true) checks each condition in order and uses the first one that's true.
        return match (true) {
            $status === 'COMPLETED' && $result === 'RESOLVED' => ['Succeeded', 'success'],
            $status === 'COMPLETED' && $result === 'ERROR' => ['Failed', 'failed'],
            $status === 'COMPLETED' => ['Completed', 'neutral'],
            $status === 'SYSTEM_FAILURE' => ['System failure', 'failed'],
            $status === 'CANCELED' => ['Canceled', 'neutral'],
            $status === 'RUNNING' => ['Running', 'running'],
            default => ['Waiting', 'neutral'],
        };
    }

    public static function isFinished(?string $status): bool
    {
        return in_array($status, Execution::FINISHED_STATUSES, true);
    }
}
