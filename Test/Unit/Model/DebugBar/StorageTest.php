<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Model\DebugBar;

use Awesoft\Developer\Model\DebugBar\Storage;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    /**
     * @var WriteInterface&MockObject
     */
    private WriteInterface $directory;

    /**
     * @var Filesystem&MockObject
     */
    private Filesystem $filesystem;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->directory = $this->createMock(WriteInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->filesystem->method('getDirectoryWrite')->willReturn($this->directory);
        $this->filesystem->method('getDirectoryRead')->willReturn($this->directory);
    }

    /**
     * @return void
     */
    public function testSaveAndLoad(): void
    {
        $written = [];
        $this->directory->method('writeFile')->willReturnCallback(
            function (string $path, string $content) use (&$written): int {
                $written[$path] = $content;
                return strlen($content);
            }
        );
        $this->directory->method('read')->willReturn([]);
        $this->directory->method('isFile')->willReturnCallback(
            function (string $path) use (&$written): bool {
                return isset($written[$path]);
            }
        );
        $this->directory->method('readFile')->willReturnCallback(
            function (string $path) use (&$written): string {
                return $written[$path];
            }
        );

        $storage = new Storage($this->filesystem);
        $token = $storage->save(['method' => 'POST', 'panels' => [['code' => 'request']]]);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $token);
        $this->assertSame(['method' => 'POST', 'panels' => [['code' => 'request']]], $storage->load($token));
        $this->assertNull($storage->load('0123456789abcdef'));
    }

    /**
     * @return void
     */
    public function testRejectsTokensThatAreNotHex(): void
    {
        $this->directory->expects($this->never())->method('isFile');

        $storage = new Storage($this->filesystem);
        $this->assertNull($storage->load('../../app/etc/env'));
        $this->assertNull($storage->load(''));
    }

    /**
     * @return void
     */
    public function testPrunesOldestProfiles(): void
    {
        $files = ['awesoft/debug_bar/a.json', 'awesoft/debug_bar/b.json', 'awesoft/debug_bar/c.json'];
        $this->directory->method('read')->willReturn($files);
        $this->directory->method('stat')->willReturnMap([
            [$files[0], ['mtime' => 300]],
            [$files[1], ['mtime' => 100]],
            [$files[2], ['mtime' => 200]],
        ]);
        $this->directory->expects($this->once())->method('delete')->with($files[1]);

        (new Storage($this->filesystem, 2))->save([]);
    }
}
