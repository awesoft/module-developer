<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar\Collector;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class Layout implements CollectorInterface
{
    /**
     * @param LayoutInterface $layout
     */
    public function __construct(
        private readonly LayoutInterface $layout,
    ) {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'layout';
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Layout';
    }

    /**
     * @return array
     */
    public function collect(): array
    {
        $handles = array_map(fn(string $handle): array => [$handle], $this->layout->getUpdate()->getHandles());

        $blocks = [];
        foreach ($this->layout->getAllBlocks() as $name => $block) {
            if ($name === 'awesoft.developer.debug_bar') {
                continue;
            }
            $blocks[] = [$name, get_class($block), $block instanceof Template ? (string)$block->getTemplate() : ''];
        }

        return [
            'summary' => sprintf('%d handles · %d blocks', count($handles), count($blocks)),
            'badge' => (string)count($blocks),
            'sections' => [
                ['title' => 'Handles', 'rows' => $handles],
                ['title' => 'Blocks', 'headers' => ['Name', 'Class', 'Template'], 'rows' => $blocks],
            ],
        ];
    }
}
