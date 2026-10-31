<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Model\App;

use Awesoft\Developer\Model\App\ExceptionHandler;
use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\Whoops\Handler\PrettyPageHandler;
use Awesoft\Developer\Model\Whoops\MagentoDataTable;
use Exception;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ExceptionHandlerTest extends TestCase
{
    /**
     * @var Config&MockObject
     */
    private Config $config;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var Bootstrap&MockObject
     */
    private Bootstrap $bootstrap;

    /**
     * @var Request&MockObject
     */
    private Request $request;

    /**
     * @var Response&MockObject
     */
    private Response $response;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->bootstrap = $this->createMock(Bootstrap::class);
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
    }

    /**
     * @param int $code
     * @param int $expected
     * @return void
     */
    #[DataProvider('statusCodeProvider')]
    public function testGetStatusCode(int $code, int $expected): void
    {
        $this->assertSame($expected, $this->createHandler()->getStatusCode(new Exception('', $code)));
    }

    /**
     * @return array
     */
    public static function statusCodeProvider(): array
    {
        return [
            'no code' => [0, 500],
            'not http' => [1, 500],
            'pdo sqlstate' => [23000, 500],
            'redirect' => [302, 500],
            'not found' => [404, 404],
            'unavailable' => [503, 503],
        ];
    }

    /**
     * @return void
     */
    public function testIsJsonRequest(): void
    {
        $handler = $this->createHandler();

        $this->request->method('isAjax')->willReturnOnConsecutiveCalls(true, false, false);
        $this->request->method('getHeader')->with('Accept')->willReturnOnConsecutiveCalls(
            'application/json, text/plain',
            'text/html'
        );

        $this->assertTrue($handler->isJsonRequest($this->request));
        $this->assertTrue($handler->isJsonRequest($this->request));
        $this->assertFalse($handler->isJsonRequest($this->request));
    }

    /**
     * @return void
     */
    public function testRendersJsonForAjaxRequests(): void
    {
        $exception = new RuntimeException('Boom', 23000);
        $this->config->method('useWhoops')->willReturn(true);
        $this->request->method('isAjax')->willReturn(true);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->response->expects($this->once())->method('clearHeader')->with('Location');
        $this->response->expects($this->once())->method('setHttpResponseCode')->with(500);
        $this->response->expects($this->once())->method('setHeader')->with('Content-Type', 'application/json', true);
        $this->response->expects($this->once())->method('setBody')->with($this->callback(
            fn(string $body): bool => json_decode($body, true)['error']['message'] === 'Boom'
        ));
        $this->response->expects($this->once())->method('sendResponse');

        $this->assertTrue($this->createHandler()->handle($this->bootstrap, $exception, $this->response, $this->request));
    }

    /**
     * @return void
     */
    public function testFallsBackToCoreHandlerWhenDisabled(): void
    {
        $this->config->method('useWhoops')->willReturn(false);
        $this->bootstrap->method('isDeveloperMode')->willReturn(true);

        $this->response->expects($this->once())->method('setHeader')->with('Content-Type', 'text/plain');
        $this->response->expects($this->once())->method('setBody')->with($this->stringContains('Boom'));

        $this->assertTrue(
            $this->createHandler()->handle($this->bootstrap, new Exception('Boom'), $this->response, $this->request)
        );
    }

    /**
     * @return void
     */
    public function testFallsBackToCoreHandlerWhenConfigFails(): void
    {
        $this->config->method('useWhoops')->willThrowException(new RuntimeException('DB down'));
        $this->bootstrap->method('isDeveloperMode')->willReturn(true);

        $this->response->expects($this->once())->method('setHeader')->with('Content-Type', 'text/plain');

        $this->assertTrue(
            $this->createHandler()->handle($this->bootstrap, new Exception('Boom'), $this->response, $this->request)
        );
    }

    /**
     * @return ExceptionHandler
     */
    private function createHandler(): ExceptionHandler
    {
        return new ExceptionHandler(
            $this->createMock(PrettyPageHandler::class),
            $this->config,
            $this->createMock(MagentoDataTable::class),
            $this->createMock(EncryptorInterface::class),
            $this->createMock(Filesystem::class),
            $this->logger,
        );
    }
}
