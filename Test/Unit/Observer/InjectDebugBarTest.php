<?php

declare(strict_types=1);

namespace Awesoft\Developer\Test\Unit\Observer;

use Awesoft\Developer\Block\DebugBar;
use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\DebugBar\Profiler;
use Awesoft\Developer\Model\DebugBar\ResponseContext;
use Awesoft\Developer\Model\DebugBar\Storage;
use Awesoft\Developer\Observer\InjectDebugBar;
use Laminas\Http\Header\HeaderInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\App\State;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InjectDebugBarTest extends TestCase
{
    /**
     * @var Config&MockObject
     */
    private Config $config;

    /**
     * @var LayoutInterface&MockObject
     */
    private LayoutInterface $layout;

    /**
     * @var Request&MockObject
     */
    private Request $request;

    /**
     * @var Response&MockObject
     */
    private Response $response;

    /**
     * @var Storage&MockObject
     */
    private Storage $storage;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->layout = $this->createMock(LayoutInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->storage = $this->createMock(Storage::class);
    }

    /**
     * @return void
     */
    public function testSkipsAjaxRedirectAndNonHtml(): void
    {
        $this->config->method('isDebugBarEnabled')->willReturn(true);
        $observer = $this->createObserver();

        $this->request->method('isAjax')->willReturnOnConsecutiveCalls(true, false, false, false);
        $this->response->method('isRedirect')->willReturnOnConsecutiveCalls(true, false, false);
        $this->response->method('getHeader')->with('Content-Type')->willReturnOnConsecutiveCalls(
            $this->createHeader('application/json'),
            $this->createHeader('text/html; charset=UTF-8')
        );

        $this->assertFalse($observer->canInject($this->request, $this->response));
        $this->assertFalse($observer->canInject($this->request, $this->response));
        $this->assertFalse($observer->canInject($this->request, $this->response));
        $this->assertTrue($observer->canInject($this->request, $this->response));
    }

    /**
     * @return void
     */
    public function testSkipsWhenDisabled(): void
    {
        $this->config->method('isDebugBarEnabled')->willReturn(false);
        $this->response->method('getHeader')->willReturn(false);

        $this->assertFalse($this->createObserver()->canInject($this->request, $this->response));
    }

    /**
     * @return void
     */
    public function testInsertsBeforeClosingBody(): void
    {
        $this->config->method('isDebugBarEnabled')->willReturn(true);
        $this->response->method('getHeader')->willReturn(false);
        $this->response->method('getBody')->willReturn('<html><body>page</BODY></html>');

        $block = $this->createMock(DebugBar::class);
        $block->method('toHtml')->willReturn('<div>bar</div>');
        $this->layout->method('createBlock')->with(DebugBar::class, DebugBar::NAME)->willReturn($block);

        $this->response->expects($this->once())->method('setBody')
            ->with('<html><body>page<div>bar</div></BODY></html>');

        $this->createObserver()->execute(new Observer([
            'event' => new Event(['request' => $this->request, 'response' => $this->response]),
        ]));
    }

    /**
     * @return void
     */
    public function testCapturesAjaxResponseAndSetsTokenHeader(): void
    {
        $this->config->method('isDebugBarEnabled')->willReturn(true);
        $this->request->method('isAjax')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getRequestUri')->willReturn('/checkout/cart/add');
        $this->response->method('getHttpResponseCode')->willReturn(200);

        $this->storage->expects($this->once())->method('save')
            ->with($this->callback(fn(array $profile): bool => $profile['method'] === 'POST'
                && $profile['url'] === '/checkout/cart/add' && $profile['status'] === 200))
            ->willReturn('0123456789abcdef');
        $this->response->expects($this->once())->method('setHeader')
            ->with(DebugBar::TOKEN_HEADER, '0123456789abcdef', true);

        $this->createObserver()->execute(new Observer([
            'event' => new Event(['request' => $this->request, 'response' => $this->response]),
        ]));
    }

    /**
     * @return void
     */
    public function testDoesNotCaptureRedirectsOrItsOwnEndpoint(): void
    {
        $this->config->method('isDebugBarEnabled')->willReturn(true);
        $this->request->method('isAjax')->willReturn(true);
        $this->request->method('getFullActionName')->willReturnOnConsecutiveCalls('checkout_cart_add', 'awesoft_debugbar_panel_index');
        $this->response->method('isRedirect')->willReturn(true);
        $this->storage->expects($this->never())->method('save');

        $observer = $this->createObserver();
        $event = new Observer(['event' => new Event(['request' => $this->request, 'response' => $this->response])]);
        $observer->execute($event);
        $observer->execute($event);
    }

    /**
     * @param string $value
     * @return HeaderInterface
     */
    private function createHeader(string $value): HeaderInterface
    {
        $header = $this->createMock(HeaderInterface::class);
        $header->method('getFieldValue')->willReturn($value);

        return $header;
    }

    /**
     * @return InjectDebugBar
     */
    private function createObserver(): InjectDebugBar
    {
        $state = $this->createMock(State::class);
        $state->method('getAreaCode')->willReturn('frontend');

        return new InjectDebugBar(
            $this->config,
            $state,
            $this->layout,
            $this->createMock(LoggerInterface::class),
            new ResponseContext(),
            $this->createMock(Profiler::class),
            $this->storage
        );
    }
}
