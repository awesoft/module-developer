<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Model\Config\Source;

use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\Config\Source\Editors;
use Awesoft\Developer\Model\Whoops\Handler\PrettyPageHandler;
use PHPUnit\Framework\TestCase;

class EditorsTest extends TestCase
{
    /**
     * @return void
     */
    public function testToOptionArray(): void
    {
        $handler = $this->createMock(PrettyPageHandler::class);
        $handler->method('getEditors')->willReturn(['phpstorm', 'neweditor']);

        $options = (new Editors($handler))->toOptionArray();

        $this->assertSame('', $options[0]['value']);
        $this->assertSame(['value' => 'phpstorm', 'label' => 'PhpStorm'], $options[1]);
        $this->assertSame(['value' => 'neweditor', 'label' => 'Neweditor'], $options[2]);
        $this->assertSame(Config::EDITOR_CUSTOM, end($options)['value']);
    }
}
