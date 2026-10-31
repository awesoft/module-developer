<?php

declare(strict_types=1);

namespace Awesoft\Developer\Plugin\Event;

use Awesoft\Developer\Model\DebugBar\EventRecorder;
use Magento\Framework\App\State;
use Magento\Framework\Event\Manager;

class RecordEvents
{
    /**
     * Only State is used here: anything that loads config would dispatch events and recurse
     *
     * @param EventRecorder $eventRecorder
     * @param State $state
     */
    public function __construct(
        private readonly EventRecorder $eventRecorder,
        private readonly State $state,
    ) {
    }

    /**
     * @param Manager $subject
     * @param string $eventName
     * @param array $data
     * @return null
     */
    public function beforeDispatch(Manager $subject, $eventName, array $data = [])
    {
        if ($this->state->getMode() === State::MODE_DEVELOPER) {
            $this->eventRecorder->record((string)$eventName);
        }

        return null;
    }
}
