<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\Whoops;

use Awesoft\Developer\Model\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

class MagentoDataTable
{
    /**
     * @param ProductMetadataInterface $productMetadata
     * @param State $state
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly ProductMetadataInterface $productMetadata,
        private readonly State $state,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    /**
     * Each value is resolved on its own so a half-booted application still shows what it can
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function getData(Request $request): array
    {
        return array_filter([
            'Version' => $this->resolve(
                fn() => $this->productMetadata->getName() . ' ' . $this->productMetadata->getEdition()
                    . ' ' . $this->productMetadata->getVersion()
            ),
            'Mode' => $this->resolve(fn() => $this->state->getMode()),
            'Area' => $this->resolve(fn() => $this->state->getAreaCode()),
            'Store' => $this->resolve(fn() => $this->storeManager->getStore()->getCode()),
            'Full Action Name' => $this->resolve(fn() => $request->getFullActionName()),
            'Route Params' => $this->resolve(fn() => $this->maskSecrets($request->getParams()) ?: null),
        ], fn($value) => $value !== null && $value !== '' && $value !== '__');
    }

    /**
     * @param array $params
     * @return array
     */
    private function maskSecrets(array $params): array
    {
        foreach ($params as $key => $value) {
            if (preg_match(Config::SECRET_KEY_PATTERN, (string)$key)) {
                $params[$key] = str_repeat('*', 8);
            }
        }

        return $params;
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    private function resolve(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return null;
        }
    }
}
