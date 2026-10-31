<?php

declare(strict_types=1);

namespace Awesoft\Developer\Observer;

use Awesoft\Developer\Block\DebugBar;
use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\DebugBar\Profiler;
use Awesoft\Developer\Model\DebugBar\ResponseContext;
use Awesoft\Developer\Model\DebugBar\Storage;
use Magento\Framework\App\Request\Http as AppRequest;
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;
use Magento\Framework\HTTP\PhpEnvironment\Response as HttpResponse;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class InjectDebugBar implements ObserverInterface
{
    /**
     * @param Config $config
     * @param State $state
     * @param LayoutInterface $layout
     * @param LoggerInterface $logger
     * @param ResponseContext $responseContext
     * @param Profiler $profiler
     * @param Storage $storage
     */
    public function __construct(
        private readonly Config $config,
        private readonly State $state,
        private readonly LayoutInterface $layout,
        private readonly LoggerInterface $logger,
        private readonly ResponseContext $responseContext,
        private readonly Profiler $profiler,
        private readonly Storage $storage,
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $request = $observer->getEvent()->getData('request');
        $response = $observer->getEvent()->getData('response');
        if (!$request instanceof HttpRequest || !$response instanceof HttpResponse) {
            return;
        }

        try {
            if ($request instanceof AppRequest
                && $request->getFullActionName() === str_replace('/', '_', DebugBar::PANEL_ROUTE)
            ) {
                return;
            }

            $this->responseContext->setResponse($response);
            if ($this->canInject($request, $response)) {
                $this->inject($response);
            } elseif ($this->canCapture($request, $response)) {
                $this->capture($request, $response);
            }
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    /**
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return bool
     */
    public function canInject(HttpRequest $request, HttpResponse $response): bool
    {
        if (($request instanceof AppRequest && $request->isAjax()) || $response->isRedirect()) {
            return false;
        }

        $contentType = $response->getHeader('Content-Type');
        if ($contentType && !str_contains(strtolower($contentType->getFieldValue()), 'text/html')) {
            return false;
        }

        return $this->config->isDebugBarEnabled($this->state->getAreaCode());
    }

    /**
     * AJAX, API and other non-page responses are stored and announced through a header for the bar to pick up
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return bool
     */
    public function canCapture(HttpRequest $request, HttpResponse $response): bool
    {
        return !$response->isRedirect() && $this->config->isDebugBarEnabled($this->state->getAreaCode());
    }

    /**
     * @param HttpResponse $response
     * @return void
     */
    private function inject(HttpResponse $response): void
    {
        $body = (string)$response->getBody();
        $position = strripos($body, '</body>');
        if ($position === false) {
            return;
        }

        $html = $this->layout->createBlock(DebugBar::class, DebugBar::NAME)->toHtml();
        $response->setBody(substr_replace($body, $html, $position, 0));
    }

    /**
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return void
     */
    private function capture(HttpRequest $request, HttpResponse $response): void
    {
        $token = $this->storage->save([
            'method' => $request->getMethod(),
            'url' => $request->getRequestUri(),
            'status' => $response->getHttpResponseCode(),
            'panels' => $this->profiler->collect(),
        ]);
        $response->setHeader(DebugBar::TOKEN_HEADER, $token, true);
    }
}
