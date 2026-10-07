<?php
declare(strict_types=1);

namespace App\Portal;

/**
 * Records every run the portal starts: who asked, what they asked for, when,
 * and how it ended. This is the traceability piece: any run can be found
 * later by its execution ID.
 *
 * Stored in a JSON file to keep the practice project simple. A real portal
 * would use a database table.
 */
final class RunLog implements RunStore
{
    public function __construct(private string $file)
    {
    }

    public function record(array $run): void
    {
        $runs = $this->load();
        array_unshift($runs, $run); // newest first
        $this->save($runs);
    }

    public function recent(int $limit = 10): array
    {
        return array_slice($this->load(), 0, $limit);
    }

    public function find(string $executionId): ?array
    {
        foreach ($this->load() as $run) {
            if ($run['executionId'] === $executionId) {
                return $run;
            }
        }

        return null;
    }

    public function updateStatus(string $executionId, string $status, ?string $result): void
    {
        $runs = $this->load();
        $changed = false;

        // "&$run" is a reference, so changes affect the array itself, not a copy.
        foreach ($runs as &$run) {
            if ($run['executionId'] === $executionId
                && ($run['status'] !== $status || $run['result'] !== $result)) {
                $run['status'] = $status;
                $run['result'] = $result;
                $changed = true;
            }
        }
        unset($run);

        if ($changed) {
            $this->save($runs);
        }
    }

    private function load(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $runs = json_decode((string) file_get_contents($this->file), true);

        return is_array($runs) ? $runs : [];
    }

    private function save(array $runs): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $this->file,
            json_encode($runs, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            LOCK_EX,
        );
    }
}
