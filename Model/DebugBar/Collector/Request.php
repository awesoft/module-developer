<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar\Collector;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\DebugBar\ResponseContext;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request as PhpRequest;
use Magento\Framework\HTTP\PhpEnvironment\Response as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;

class Request implements CollectorInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param ResponseContext $responseContext
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
        private readonly State $state,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResponseContext $responseContext,
    ) {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'request';
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Request';
    }

    /**
     * @return array
     */
    public function collect(): array
    {
        $fullActionName = $this->request instanceof HttpRequest ? $this->request->getFullActionName() : '';
        $response = $this->getResponse();
        $statusCode = $response?->getHttpResponseCode();
        $pageCache = $this->getPageCacheStatus($response);
        if ($pageCache === 'HIT') {
            // served by the built-in full page cache: no routing or layout ran for this request
            $fullActionName = 'page cache hit';
        } elseif (($fullActionName === '' || $fullActionName === '__') && $this->request instanceof PhpRequest) {
            // REST and GraphQL requests have no action name, show the endpoint instead
            $fullActionName = (string)$this->request->getPathInfo();
        }

        $rows = [
            ['Full Action Name', $fullActionName],
            ['Page Cache', $pageCache ?: 'Not used'],
            ['Method', $this->request instanceof PhpRequest ? $this->request->getMethod() : ''],
            ['Status', $statusCode],
            ['Area', $this->state->getAreaCode()],
            ['Store', $this->storeManager->getStore()->getCode()],
        ];

        $params = [];
        foreach ($this->request->getParams() as $key => $value) {
            if (preg_match(Config::SECRET_KEY_PATTERN, (string)$key)) {
                $value = str_repeat('*', 8);
            }
            $params[] = [$key, is_scalar($value) ? (string)$value : json_encode($value)];
        }

        return [
            'summary' => $fullActionName . ($statusCode ? ' · ' . $statusCode : ''),
            'badge' => $statusCode ? (string)$statusCode : '',
            'status' => match (true) {
                !$statusCode => '',
                $statusCode >= 500 => 'error',
                $statusCode >= 400 => 'warning',
                default => 'ok',
            },
            'sections' => [
                ['rows' => $rows],
                ['title' => 'Params', 'headers' => ['Name', 'Value'], 'rows' => $params],
            ],
        ];
    }

    /**
     * HIT or MISS from the debug header the built-in page cache sends in developer mode
     *
     * @param HttpResponse|null $response
     * @return string
     */
    private function getPageCacheStatus(?HttpResponse $response): string
    {
        $header = $response?->getHeader('X-Magento-Cache-Debug');

        return $header ? (string)$header->getFieldValue() : '';
    }

    /**
     * @return HttpResponse|null
     */
    private function getResponse(): ?HttpResponse
    {
        $response = $this->responseContext->getResponse() ?? $this->response;

        return $response instanceof HttpResponse ? $response : null;
    }
}
