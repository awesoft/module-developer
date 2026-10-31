<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar\Collector;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State;

class Environment implements CollectorInterface
{
    /**
     * @param ProductMetadataInterface $productMetadata
     * @param State $state
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        private readonly ProductMetadataInterface $productMetadata,
        private readonly State $state,
        private readonly TypeListInterface $cacheTypeList,
    ) {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'environment';
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Environment';
    }

    /**
     * @return array
     */
    public function collect(): array
    {
        $caches = [];
        $disabled = 0;
        foreach ($this->cacheTypeList->getTypes() as $type) {
            $enabled = (bool)$type->getStatus();
            $disabled += $enabled ? 0 : 1;
            $caches[] = [$type->getId(), (string)$type->getCacheType(), $enabled ? 'Enabled' : 'Disabled'];
        }

        $invalidated = array_keys($this->cacheTypeList->getInvalidated());

        return [
            'summary' => 'PHP ' . PHP_VERSION . ' · Magento ' . $this->productMetadata->getVersion(),
            'badge' => '',
            'sections' => [
                [
                    'rows' => [
                        ['Magento', $this->productMetadata->getEdition() . ' ' . $this->productMetadata->getVersion()],
                        ['Mode', $this->state->getMode()],
                        ['PHP', PHP_VERSION . ' (' . PHP_SAPI . ')'],
                        ['Disabled caches', $disabled],
                        ['Invalidated caches', $invalidated ? implode(', ', $invalidated) : 'None'],
                    ],
                ],
                ['title' => 'Cache Types', 'headers' => ['Code', 'Name', 'Status'], 'rows' => $caches],
            ],
        ];
    }
}
