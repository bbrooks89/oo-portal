<?php
declare(strict_types=1);

use App\Oo\FakeFlowRunner;
use App\Portal\RunLog;
use App\Portal\RunService;
use PHPUnit\Framework\TestCase;
use App\Oo\FlowRunner;
use App\Oo\OoException;

final class RunServiceTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/runs-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testStartingARunRecordsItInTheLog(): void
    {
        $runLog = new RunLog($this->logFile);
        $service = new RunService(new FakeFlowRunner(), $runLog);

        $id = $service->start(
            flow: ['uuid' => 'firewall-maintenance', 'name' => 'Firewall'],
            flowId: 'firewall-maintenance',
            inputs: ['action' => 'open'],
            requester: 'Brad',
        );

        $run = $runLog->find($id);
        $this->assertNotNull($run);
        $this->assertSame('Brad', $run['requester']);
    }

    public function testNewRunIsLoggedAsRunning(): void
    {
        $runLog = new RunLog($this->logFile);
        $service = new RunService(new FakeFlowRunner(), $runLog);

        $id = $service->start(
            flow: ['uuid' => 'firewall-maintenance', 'name' => 'Firewall'],
            flowId: 'firewall-maintenance',
            inputs: ['action' => 'open'],
            requester: 'Brad',
        );

        $run = $runLog->find($id);

        $this->assertSame('RUNNING', $run['status']);
        $this->assertSame('open', $run['inputs']['action']);
    }
    public function testNothingIsLoggedWhenOoIsUnreachable(): void
    {
        //$runner = $this->createMock(FlowRunner::class);
        $runner = $this->createStub(FlowRunner::class);
        $runner->method('startFlow')
            ->willThrowException(new OoException('Connection refused'));

        $runLog = new RunLog($this->logFile);
        $service = new RunService($runner, $runLog);

        try {
            $service->start(
                flow: ['uuid' => 'firewall-maintenance', 'name' => 'Firewall'],
                flowId: 'firewall-maintenance',
                inputs: ['action' => 'open'],
                requester: 'Brad',
            );
            $this->fail('Expected an OoException');
        } catch (OoException $e) {
            // expected: OO was unreachable
        }

        $this->assertSame([], $runLog->recent());
    }

    public function testStartFlowIsCalledWithTheFlowUuid(): void
    {
        $runner = $this->createMock(FlowRunner::class);
        $runner->expects($this->once())
            ->method('startFlow')
            ->with('firewall-maintenance', ['action' => 'open'], $this->anything())
            ->willReturn('12345');

        $runLog = new RunLog($this->logFile);
        $service = new RunService($runner, $runLog);

        $service->start(
            flow: ['uuid' => 'firewall-maintenance', 'name' => 'Firewall'],
            flowId: 'firewall-maintenance',
            inputs: ['action' => 'open'],
            requester: 'Brad',
        );
    }
}