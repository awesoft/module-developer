<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Model\Whoops\Handler;

use Awesoft\Developer\Model\Whoops\Handler\PrettyPageHandler;
use PHPUnit\Framework\TestCase;

class PrettyPageHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function testMapPathUsesLongestMatchingPrefix(): void
    {
        $handler = new PrettyPageHandler();
        $handler->setPathMapping(['/app' => '/host', '/app/vendor' => '/vendor-host']);

        $this->assertSame('/host/app/code/A.php', $handler->mapPath('/app/app/code/A.php'));
        $this->assertSame('/vendor-host/x/B.php', $handler->mapPath('/app/vendor/x/B.php'));
        $this->assertSame('/application/C.php', $handler->mapPath('/application/C.php'));
    }

    /**
     * @return void
     */
    public function testEditorHrefUsesMappedPath(): void
    {
        $handler = new PrettyPageHandler();
        $handler->setEditor('phpstorm');
        $handler->setPathMapping(['/app' => '/host']);

        $this->assertSame(
            'phpstorm://open?file=' . rawurlencode('/host/A.php') . '&line=12',
            $handler->getEditorHref('/app/A.php', 12)
        );
    }

    /**
     * @return void
     */
    public function testCustomEditorTemplate(): void
    {
        $handler = new PrettyPageHandler();
        $handler->addEditor('custom', 'myeditor://open?file=%file&line=%line');
        $handler->setEditor('custom');

        $this->assertSame('myeditor://open?file=' . rawurlencode('/A.php') . '&line=3', $handler->getEditorHref('/A.php', 3));
        $this->assertContains('phpstorm', $handler->getEditors());
    }
}
