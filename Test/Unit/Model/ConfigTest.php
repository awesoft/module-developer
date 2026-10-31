<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Model;

use Awesoft\Developer\Model\Config;
use Magento\Developer\Helper\Data as DeveloperHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var State&MockObject
     */
    private State $state;

    /**
     * @var DeveloperHelper&MockObject
     */
    private DeveloperHelper $developerHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->state = $this->createMock(State::class);
        $this->developerHelper = $this->createMock(DeveloperHelper::class);
    }

    /**
     * @return void
     */
    public function testConfigIsIgnoredOutsideDeveloperMode(): void
    {
        $this->state->method('getMode')->willReturn(State::MODE_PRODUCTION);
        $this->scopeConfig->expects($this->never())->method('getValue');

        $config = $this->createConfig();
        $this->assertFalse($config->useWhoops());
        $this->assertSame('', $config->getWhoopsEditor());
        $this->assertSame([], $config->getWhoopsPathMapping());
        $this->assertFalse($config->isDebugBarEnabled('frontend'));
    }

    /**
     * @return void
     */
    public function testWhoopsPathMappingAndHiddenKeys(): void
    {
        $this->state->method('getMode')->willReturn(State::MODE_DEVELOPER);
        $this->scopeConfig->method('getValue')->willReturnMap([
            [Config::PATH_WHOOPS_PATH_MAPPING, 'default', null, "/app/ = /Users/me/project/\ninvalid\n=/nowhere\n"],
            [Config::PATH_WHOOPS_HIDDEN_KEYS, 'default', null, "MY_KEY\r\n, OTHER ,"],
        ]);

        $config = $this->createConfig();
        $this->assertSame(['/app' => '/Users/me/project'], $config->getWhoopsPathMapping());
        $this->assertSame(['MY_KEY', 'OTHER'], $config->getWhoopsHiddenKeys());
    }

    /**
     * @param string $area
     * @param bool $enabled
     * @param bool $areaEnabled
     * @param bool $devAllowed
     * @param bool $expected
     * @return void
     */
    #[DataProvider('debugBarProvider')]
    public function testIsDebugBarEnabled(
        string $area,
        bool $enabled,
        bool $areaEnabled,
        bool $devAllowed,
        bool $expected
    ): void {
        $this->state->method('getMode')->willReturn(State::MODE_DEVELOPER);
        $this->scopeConfig->method('getValue')->willReturnMap([
            [Config::PATH_DEBUG_BAR, 'default', null, $enabled ? '1' : '0'],
            [Config::PATH_DEBUG_BAR_FRONTEND, 'default', null, $areaEnabled ? '1' : '0'],
            [Config::PATH_DEBUG_BAR_ADMINHTML, 'default', null, $areaEnabled ? '1' : '0'],
            [Config::PATH_DEBUG_BAR_API, 'default', null, $areaEnabled ? '1' : '0'],
        ]);
        $this->developerHelper->method('isDevAllowed')->willReturn($devAllowed);

        $this->assertSame($expected, $this->createConfig()->isDebugBarEnabled($area));
    }

    /**
     * @return array
     */
    public static function debugBarProvider(): array
    {
        return [
            'frontend all on' => ['frontend', true, true, true, true],
            'adminhtml all on' => ['adminhtml', true, true, true, true],
            'rest api' => ['webapi_rest', true, true, true, true],
            'graphql' => ['graphql', true, true, true, true],
            'api disabled' => ['webapi_rest', true, false, true, false],
            'other area' => ['crontab', true, true, true, false],
            'disabled' => ['frontend', false, true, true, false],
            'area disabled' => ['frontend', true, false, true, false],
            'ip not allowed' => ['frontend', true, true, false, false],
        ];
    }

    /**
     * @return Config
     */
    private function createConfig(): Config
    {
        return new Config($this->scopeConfig, $this->state, $this->developerHelper);
    }
}
