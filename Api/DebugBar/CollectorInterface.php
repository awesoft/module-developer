<?php

declare(strict_types=1);

namespace Awesoft\Developer\Api\DebugBar;

/**
 * A debug bar panel. Register new panels on Awesoft\Developer\Model\DebugBar\CollectorPool in di.xml.
 */
interface CollectorInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * Panel data:
     * [
     *     'summary' => 'one-line summary shown in the panel header and as the tab tooltip',
     *     'badge' => 'short value shown on the tab', // optional, defaults to the summary; '' for none
     *     'status' => 'ok|warning|error', // optional, colours the tab badge
     *     'sections' => [
     *         [
     *             'title' => 'optional',
     *             'headers' => ['optional'],
     *             'formats' => [1 => 'sql'], // optional, column index => highlighter
     *             'rows' => [['cell', 'cell'], ...],
     *         ],
     *     ],
     * ]
     *
     * @return array{summary?: string, badge?: string, status?: string, sections: array<int, array{title?: string, headers?: string[], formats?: array, rows: array}>}
     */
    public function collect(): array;
}
