<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use InvalidArgumentException;

class CollectorPool
{
    /**
     * @param CollectorInterface[] $collectors
     */
    public function __construct(
        private readonly array $collectors = [],
    ) {
        foreach ($collectors as $name => $collector) {
            if (!$collector instanceof CollectorInterface) {
                throw new InvalidArgumentException(
                    sprintf('Debug bar collector "%s" must implement %s', $name, CollectorInterface::class)
                );
            }
        }
    }

    /**
     * @return CollectorInterface[]
     */
    public function getCollectors(): array
    {
        return $this->collectors;
    }
}
