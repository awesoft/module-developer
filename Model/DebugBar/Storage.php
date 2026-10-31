<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\DebugBar;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Profiles of AJAX/JSON requests, kept in var/ so the debug bar can load them by token
 */
class Storage
{
    private const DIRECTORY = 'awesoft/debug_bar';
    private const TOKEN_PATTERN = '/^[a-f0-9]{16}$/';

    /**
     * @param Filesystem $filesystem
     * @param int $limit
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly int $limit = 100,
    ) {
    }

    /**
     * @param array $profile
     * @return string token
     */
    public function save(array $profile): string
    {
        $token = bin2hex(random_bytes(8));
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->writeFile(
            $this->getPath($token),
            json_encode($profile, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );
        $this->prune($directory);

        return $token;
    }

    /**
     * @param string $token
     * @return array|null
     */
    public function load(string $token): ?array
    {
        if (!preg_match(self::TOKEN_PATTERN, $token)) {
            return null;
        }

        $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        if (!$directory->isFile($this->getPath($token))) {
            return null;
        }

        $profile = json_decode($directory->readFile($this->getPath($token)), true);

        return is_array($profile) ? $profile : null;
    }

    /**
     * Keep only the newest profiles
     *
     * @param WriteInterface $directory
     * @return void
     */
    private function prune(WriteInterface $directory): void
    {
        $files = $directory->read(self::DIRECTORY);
        if (count($files) <= $this->limit) {
            return;
        }

        $modified = [];
        foreach ($files as $file) {
            $modified[$file] = $directory->stat($file)['mtime'] ?? 0;
        }
        asort($modified);

        foreach (array_slice(array_keys($modified), 0, count($files) - $this->limit) as $file) {
            $directory->delete($file);
        }
    }

    /**
     * @param string $token
     * @return string
     */
    private function getPath(string $token): string
    {
        return self::DIRECTORY . '/' . $token . '.json';
    }
}
