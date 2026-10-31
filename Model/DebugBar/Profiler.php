<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar;

use Throwable;

class Profiler
{
    /**
     * @param CollectorPool $collectorPool
     */
    public function __construct(
        private readonly CollectorPool $collectorPool,
    ) {
    }

    /**
     * A failing collector shows its error instead of breaking the whole bar
     *
     * @return array<int, array{code: string, label: string, summary: string, badge: string, status: string, sections: array}>
     */
    public function collect(): array
    {
        $panels = [];
        foreach ($this->collectorPool->getCollectors() as $collector) {
            try {
                $data = $collector->collect();
            } catch (Throwable $e) {
                $data = [
                    'summary' => 'error',
                    'status' => 'error',
                    'sections' => [['rows' => [[get_class($e) . ': ' . $e->getMessage()]]]],
                ];
            }

            $panels[] = [
                'code' => $collector->getCode(),
                'label' => $collector->getLabel(),
                'summary' => (string)($data['summary'] ?? ''),
                'badge' => (string)($data['badge'] ?? $data['summary'] ?? ''),
                'status' => (string)($data['status'] ?? ''),
                'sections' => $data['sections'] ?? [],
            ];
        }

        return $panels;
    }
}
