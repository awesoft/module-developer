<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar;

use Magento\Framework\HTTP\PhpEnvironment\Response as HttpResponse;

/**
 * The response actually being sent; on a full page cache hit Magento swaps in the cached response object,
 * so the shared ResponseInterface instance no longer reflects it.
 */
class ResponseContext
{
    /**
     * @var HttpResponse|null
     */
    private ?HttpResponse $response = null;

    /**
     * @param HttpResponse $response
     * @return void
     */
    public function setResponse(HttpResponse $response): void
    {
        $this->response = $response;
    }

    /**
     * @return HttpResponse|null
     */
    public function getResponse(): ?HttpResponse
    {
        return $this->response;
    }
}
