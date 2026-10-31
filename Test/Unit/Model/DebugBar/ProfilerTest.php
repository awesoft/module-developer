<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Model\DebugBar;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use Awesoft\Developer\Model\DebugBar\CollectorPool;
use Awesoft\Developer\Model\DebugBar\Profiler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProfilerTest extends TestCase
{
    /**
     * @return void
     */
    public function testBadgeDefaultsToSummaryAndFailuresBecomeErrorPanels(): void
    {
        $profiler = new Profiler(new CollectorPool([
            'short' => $this->createCollector('short', ['summary' => '12 queries · 3 ms', 'badge' => '12', 'sections' => []]),
            'long' => $this->createCollector('long', ['summary' => 'only summary', 'sections' => []]),
            'none' => $this->createCollector('none', ['summary' => 'no badge', 'badge' => '', 'sections' => []]),
            'broken' => $this->createCollector('broken', new RuntimeException('Boom')),
        ]));

        $panels = array_column($profiler->collect(), null, 'code');

        $this->assertSame('12', $panels['short']['badge']);
        $this->assertSame('12 queries · 3 ms', $panels['short']['summary']);
        $this->assertSame('only summary', $panels['long']['badge']);
        $this->assertSame('', $panels['none']['badge']);
        $this->assertSame('error', $panels['broken']['status']);
        $this->assertStringContainsString('Boom', $panels['broken']['sections'][0]['rows'][0][0]);
    }

    /**
     * @param string $code
     * @param array|RuntimeException $result
     * @return CollectorInterface
     */
    private function createCollector(string $code, array|RuntimeException $result): CollectorInterface
    {
        $collector = $this->createMock(CollectorInterface::class);
        $collector->method('getCode')->willReturn($code);
        $collector->method('getLabel')->willReturn(ucfirst($code));
        if ($result instanceof RuntimeException) {
            $collector->method('collect')->willThrowException($result);
        } else {
            $collector->method('collect')->willReturn($result);
        }

        return $collector;
    }
}
