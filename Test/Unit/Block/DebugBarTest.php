<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Block;

use Awesoft\Developer\Block\DebugBar;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DebugBarTest extends TestCase
{
    /**
     * @return void
     */
    public function testHasFormat(): void
    {
        /** @var DebugBar $block */
        $block = (new ReflectionClass(DebugBar::class))->newInstanceWithoutConstructor();

        $database = ['sections' => [['rows' => []], ['formats' => [1 => 'sql'], 'rows' => []]]];
        $request = ['sections' => [['rows' => []]]];

        $this->assertTrue($block->hasFormat($database, 'sql'));
        $this->assertFalse($block->hasFormat($request, 'sql'));
    }
}
