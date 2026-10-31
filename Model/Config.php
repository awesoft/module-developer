<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model;

use Magento\Developer\Helper\Data as DeveloperHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;

class Config
{
    public const PATH_USE_WHOOPS = 'awesoft/developer/use_whoops';
    public const PATH_WHOOPS_EDITOR = 'awesoft/developer/whoops_editor';
    public const PATH_WHOOPS_EDITOR_URL = 'awesoft/developer/whoops_editor_url';
    public const PATH_WHOOPS_PATH_MAPPING = 'awesoft/developer/whoops_path_mapping';
    public const PATH_WHOOPS_HIDDEN_KEYS = 'awesoft/developer/whoops_hidden_keys';
    public const PATH_DEBUG_BAR = 'awesoft/developer/debug_bar';
    public const PATH_DEBUG_BAR_FRONTEND = 'awesoft/developer/debug_bar_frontend';
    public const PATH_DEBUG_BAR_ADMINHTML = 'awesoft/developer/debug_bar_adminhtml';
    public const PATH_DEBUG_BAR_API = 'awesoft/developer/debug_bar_api';

    public const EDITOR_CUSTOM = 'custom';
    public const SECRET_KEY_PATTERN = '/pass|secret|key|token|auth|session|cookie/i';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param State $state
     * @param DeveloperHelper $developerHelper
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly State $state,
        private readonly DeveloperHelper $developerHelper,
    ) {
    }

    /**
     * @return bool
     */
    public function isDeveloperMode(): bool
    {
        return $this->state->getMode() === State::MODE_DEVELOPER;
    }

    /**
     * @return bool
     */
    public function useWhoops(): bool
    {
        return (bool)$this->getValue(self::PATH_USE_WHOOPS);
    }

    /**
     * @return string
     */
    public function getWhoopsEditor(): string
    {
        return (string)$this->getValue(self::PATH_WHOOPS_EDITOR);
    }

    /**
     * @return string
     */
    public function getWhoopsEditorUrl(): string
    {
        return trim((string)$this->getValue(self::PATH_WHOOPS_EDITOR_URL));
    }

    /**
     * Container path => host path pairs, one "from=to" per line
     *
     * @return array<string, string>
     */
    public function getWhoopsPathMapping(): array
    {
        $mapping = [];
        foreach ($this->getLines(self::PATH_WHOOPS_PATH_MAPPING) as $line) {
            if (!str_contains($line, '=')) {
                continue;
            }

            [$from, $to] = array_map('trim', explode('=', $line, 2));
            if ($from !== '' && $to !== '') {
                $mapping[rtrim($from, '/')] = rtrim($to, '/');
            }
        }

        return $mapping;
    }

    /**
     * @return string[]
     */
    public function getWhoopsHiddenKeys(): array
    {
        return $this->getLines(self::PATH_WHOOPS_HIDDEN_KEYS);
    }

    /**
     * @param string $areaCode
     * @return bool
     */
    public function isDebugBarEnabled(string $areaCode): bool
    {
        $areaPath = match ($areaCode) {
            Area::AREA_FRONTEND => self::PATH_DEBUG_BAR_FRONTEND,
            Area::AREA_ADMINHTML => self::PATH_DEBUG_BAR_ADMINHTML,
            Area::AREA_WEBAPI_REST, Area::AREA_GRAPHQL => self::PATH_DEBUG_BAR_API,
            default => null,
        };

        return $areaPath && $this->getValue($areaPath) && $this->isDebugBarAllowed();
    }

    /**
     * Debug bar on and the client allowed, regardless of area (e.g. loading a stored admin AJAX profile)
     *
     * @return bool
     */
    public function isDebugBarAllowed(): bool
    {
        return $this->getValue(self::PATH_DEBUG_BAR) && $this->developerHelper->isDevAllowed();
    }

    /**
     * @param string $key
     * @return string[]
     */
    private function getLines(string $key): array
    {
        $lines = preg_split('/[\r\n,]+/', (string)$this->getValue($key), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(array_map('trim', $lines)));
    }

    /**
     * @param string $key
     * @return mixed
     */
    private function getValue(string $key): mixed
    {
        if (!$this->isDeveloperMode()) {
            return false;
        }

        return $this->scopeConfig->getValue($key);
    }
}
