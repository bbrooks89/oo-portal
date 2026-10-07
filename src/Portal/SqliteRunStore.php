<?php
declare(strict_types=1);

namespace App\Portal;

use PDO;

final class SqliteRunStore implements RunStore
{
    public function __construct(private PDO $db)
    {
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS runs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                execution_id TEXT NOT NULL UNIQUE,
                flow_id TEXT NOT NULL,
                flow_name TEXT NOT NULL,
                requester TEXT NOT NULL,
                inputs TEXT NOT NULL,
                requested_at TEXT NOT NULL,
                status TEXT NOT NULL,
                result TEXT
            )'
        );
    }

    public function record(array $run): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO runs (execution_id, flow_id, flow_name, requester, inputs, requested_at, status, result)
             VALUES (:execution_id, :flow_id, :flow_name, :requester, :inputs, :requested_at, :status, :result)'
        );
        $stmt->execute([
            'execution_id' => $run['executionId'],
            'flow_id' => $run['flowId'],
            'flow_name' => $run['flowName'],
            'requester' => $run['requester'],
            'inputs' => json_encode($run['inputs'], JSON_THROW_ON_ERROR),
            'requested_at' => $run['requestedAt'],
            'status' => $run['status'],
            'result' => $run['result'],
        ]);
    }

    public function recent(int $limit = 10): array
    {
        $stmt = $this->db->prepare('SELECT * FROM runs ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $runs = [];
        foreach ($stmt->fetchAll() as $row) {
            $runs[] = $this->toRun($row);
        }
        return $runs;
    }

    public function find(string $executionId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM runs WHERE execution_id = :id');
        $stmt->execute(['id' => $executionId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->toRun($row);
    }

    public function updateStatus(string $executionId, string $status, ?string $result): void
    {
        $stmt = $this->db->prepare(
            'UPDATE runs SET status = :status, result = :result WHERE execution_id = :id'
        );
        $stmt->execute(['status' => $status, 'result' => $result, 'id' => $executionId]);
    }

    /** Converts a database row back into the same array shape RunLog uses. */
    private function toRun(array $row): array
    {
        return [
            'executionId' => $row['execution_id'],
            'flowId' => $row['flow_id'],
            'flowName' => $row['flow_name'],
            'requester' => $row['requester'],
            'inputs' => json_decode($row['inputs'], true, flags: JSON_THROW_ON_ERROR),
            'requestedAt' => $row['requested_at'],
            'status' => $row['status'],
            'result' => $row['result'],
        ];
    }
}