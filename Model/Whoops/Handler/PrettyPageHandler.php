<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\Whoops\Handler;

use Whoops\Handler\PrettyPageHandler as WhoopsPrettyPageHandler;

class PrettyPageHandler extends WhoopsPrettyPageHandler
{
    /**
     * @var array<string, string>
     */
    private array $pathMapping = [];

    /**
     * @return array
     */
    public function getEditors(): array
    {
        return array_keys($this->editors);
    }

    /**
     * @param array<string, string> $pathMapping
     * @return $this
     */
    public function setPathMapping(array $pathMapping): static
    {
        uksort($pathMapping, fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        $this->pathMapping = $pathMapping;

        return $this;
    }

    /**
     * @param string $filePath
     * @param int $line
     * @return string|bool
     */
    public function getEditorHref($filePath, $line)
    {
        return parent::getEditorHref($this->mapPath((string)$filePath), $line);
    }

    /**
     * Translate a server path into the matching local path, e.g. docker mount => host checkout
     *
     * @param string $filePath
     * @return string
     */
    public function mapPath(string $filePath): string
    {
        foreach ($this->pathMapping as $from => $to) {
            if ($filePath === $from || str_starts_with($filePath, $from . '/')) {
                return $to . substr($filePath, strlen($from));
            }
        }

        return $filePath;
    }
}
