<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar;

class EventRecorder
{
    /**
     * @var array<string, int>
     */
    private array $events = [];

    /**
     * @param string $eventName
     * @return void
     */
    public function record(string $eventName): void
    {
        $this->events[$eventName] = ($this->events[$eventName] ?? 0) + 1;
    }

    /**
     * Event name => dispatch count, in first-dispatch order
     *
     * @return array<string, int>
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
