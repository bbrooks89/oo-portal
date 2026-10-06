<?php
declare(strict_types=1);

use App\Portal\FlowCatalog;
use PHPUnit\Framework\TestCase;

final class FlowCatalogTest extends TestCase
{
    public function testValidInputsHaveNoErrors(): void
    {
        $catalog = FlowCatalog::default();

        $result = $catalog->validate('firewall-maintenance', [
            'client' => 'state-01',
            'action' => 'open',
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame('open', $result['inputs']['action']);
    }

    public function testMissingInputIsRejected(): void
    {
        $catalog = FlowCatalog::default();

        $result = $catalog->validate('firewall-maintenance', ['client' => 'state-01']);

        $this->assertArrayHasKey('action', $result['errors']);
    }
    public function testBadServerNameIsRejected(): void
    {
        $catalog = FlowCatalog::default();

        $result = $catalog->validate('service-restart', [
            'server' => 'bad name!',
            'service' => 'W3SVC',
        ]);

        //var_dump($result['errors']);
        $this->assertArrayHasKey('server', $result['errors']);
    }

}