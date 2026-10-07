<?php
declare(strict_types=1);

use App\Portal\SqliteRunStore;
use PHPUnit\Framework\TestCase;

final class SqliteRunStoreTest extends TestCase
{
    private SqliteRunStore $store;

    protected function setUp(): void
    {
        $this->store = new SqliteRunStore(new PDO('sqlite::memory:'));
    }

    public function testRecordedRunCanBeFound(): void
    {
        $this->store->record([
            'executionId' => '111',
            'flowId' => 'firewall-maintenance',
            'flowName' => 'Firewall',
            'requester' => 'Brad',
            'inputs' => ['action' => 'open'],
            'requestedAt' => '2026-10-08T09:00:00-04:00',
            'status' => 'RUNNING',
            'result' => null,
        ]);

        $run = $this->store->find('111');

        $this->assertSame('Brad', $run['requester']);
        $this->assertSame('open', $run['inputs']['action']);
    }

    public function testUpdateStatusChangesTheRun(): void
    {
        $this->store->record([
            'executionId' => '111',
            'flowId' => 'firewall-maintenance',
            'flowName' => 'Firewall',
            'requester' => 'Brad',
            'inputs' => ['action' => 'open'],
            'requestedAt' => '2026-10-08T09:00:00-04:00',
            'status' => 'RUNNING',
            'result' => null,
        ]);

        $run = $this->store->find('111');
        $this->assertSame('RUNNING', $run['status']);   // before

        $this->store->updateStatus('111', 'COMPLETED', 'RESOLVED');

        $run = $this->store->find('111');
        $this->assertSame('COMPLETED', $run['status']); // after
        $this->assertSame('RESOLVED', $run['result']);
    }
}