<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar\Collector;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use Magento\Framework\App\Request\Http as HttpRequest;

class Performance implements CollectorInterface
{
    /**
     * @param HttpRequest $request
     * @param int $warningMs
     * @param int $errorMs
     */
    public function __construct(
        private readonly HttpRequest $request,
        private readonly int $warningMs = 1000,
        private readonly int $errorMs = 3000,
    ) {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'performance';
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Performance';
    }

    /**
     * @return array
     */
    public function collect(): array
    {
        $start = (float)($this->request->getServer('REQUEST_TIME_FLOAT') ?: microtime(true));
        $elapsedMs = (microtime(true) - $start) * 1000;
        $elapsed = sprintf('%.0f ms', $elapsedMs);
        $memory = sprintf('%.1f MB', memory_get_peak_usage(true) / 1048576);

        return [
            'summary' => $elapsed . ' · ' . $memory,
            'badge' => $elapsed,
            'status' => $elapsedMs >= $this->errorMs ? 'error' : ($elapsedMs >= $this->warningMs ? 'warning' : 'ok'),
            'sections' => [
                [
                    'rows' => [
                        ['Time until response', $elapsed],
                        ['Peak memory', $memory],
                        ['Memory limit', ini_get('memory_limit')],
                        ['OPcache', function_exists('opcache_get_status') && opcache_get_status(false) ? 'On' : 'Off'],
                        ['Xdebug', extension_loaded('xdebug') ? 'Loaded' : 'Not loaded'],
                    ],
                ],
            ],
        ];
    }
}
