<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar\Collector;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use Awesoft\Developer\Model\DebugBar\EventRecorder;

class Events implements CollectorInterface
{
    /**
     * @param EventRecorder $eventRecorder
     */
    public function __construct(
        private readonly EventRecorder $eventRecorder,
    ) {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'events';
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Events';
    }

    /**
     * @return array
     */
    public function collect(): array
    {
        $events = $this->eventRecorder->getEvents();
        $rows = [];
        foreach ($events as $name => $count) {
            $rows[] = [$name, $count];
        }

        return [
            'summary' => sprintf('%d events · %d dispatches', count($events), array_sum($events)),
            'badge' => (string)count($events),
            'sections' => [
                ['headers' => ['Event', 'Count'], 'rows' => $rows],
            ],
        ];
    }
}
