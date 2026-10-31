<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar\Collector;

use Awesoft\Developer\Api\DebugBar\CollectorInterface;
use Magento\Framework\App\ResourceConnection;

class Database implements CollectorInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param int $limit
     * @param int $warningQueryCount
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly int $limit = 50,
        private readonly int $warningQueryCount = 100,
    ) {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'database';
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Database';
    }

    /**
     * @return array
     */
    public function collect(): array
    {
        $profiler = $this->resourceConnection->getConnection()->getProfiler();
        if (!$profiler || !$profiler->getEnabled()) {
            return [
                'summary' => 'profiler off',
                'badge' => 'off',
                'sections' => [
                    [
                        'rows' => [
                            ['The DB profiler is disabled. Add this to app/etc/env.php under db/connection/default:'],
                            ["'profiler' => ['class' => \\Magento\\Framework\\DB\\Profiler::class, 'enabled' => true]"],
                        ],
                    ],
                ],
            ];
        }

        $queries = [];
        foreach ($profiler->getQueryProfiles() ?: [] as $profile) {
            $queries[] = [$profile->getElapsedSecs(), $profile->getQuery()];
        }
        usort($queries, fn(array $a, array $b): int => $b[0] <=> $a[0]);

        $rows = array_map(
            fn(array $query): array => [sprintf('%.2f ms', $query[0] * 1000), $query[1]],
            array_slice($queries, 0, $this->limit)
        );

        $total = sprintf('%d queries · %.0f ms', $profiler->getTotalNumQueries(), $profiler->getTotalElapsedSecs() * 1000);

        return [
            'summary' => $total,
            'badge' => (string)$profiler->getTotalNumQueries(),
            'status' => $profiler->getTotalNumQueries() > $this->warningQueryCount ? 'warning' : 'ok',
            'sections' => [
                [
                    'title' => sprintf('Slowest %d queries', count($rows)),
                    'headers' => ['Time', 'Query'],
                    'formats' => [1 => 'sql'],
                    'rows' => $rows,
                ],
            ],
        ];
    }
}
