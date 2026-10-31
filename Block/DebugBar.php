<?php

declare(strict_types=1);

namespace Awesoft\Developer\Block;

use Awesoft\Developer\Model\DebugBar\Profiler;
use Magento\Framework\Url;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class DebugBar extends Template
{
    public const NAME = 'awesoft.developer.debug_bar';
    public const PANEL_ROUTE = 'awesoft_debugbar/panel/index';
    public const TOKEN_HEADER = 'X-Awesoft-Debug-Token';

    /**
     * @var string
     */
    protected $_template = 'Awesoft_Developer::debug-bar.phtml';

    /**
     * @var array|null
     */
    private ?array $panels = null;

    /**
     * @param Context $context
     * @param Profiler $profiler
     * @param Url $frontendUrl
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Profiler $profiler,
        private readonly Url $frontendUrl,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Panels of the current request, unless stored panels of an AJAX request were set
     *
     * @return array<int, array{code: string, label: string, summary: string, status: string, sections: array}>
     */
    public function getPanels(): array
    {
        return $this->panels ??= $this->profiler->collect();
    }

    /**
     * @param array $panels
     * @return $this
     */
    public function setPanels(array $panels): static
    {
        $this->panels = $panels;

        return $this;
    }

    /**
     * @param array $panel
     * @param string $format
     * @return bool
     */
    public function hasFormat(array $panel, string $format): bool
    {
        foreach ($panel['sections'] as $section) {
            if (in_array($format, $section['formats'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Storefront endpoint serving stored AJAX profiles, also used from the admin
     *
     * @return string
     */
    public function getPanelUrl(): string
    {
        return $this->frontendUrl->getUrl(self::PANEL_ROUTE, ['_nosid' => true]);
    }

    /**
     * @param string $name tabs|panels
     * @return string
     */
    public function renderPartial(string $name): string
    {
        return $this->fetchView($this->getTemplateFile('Awesoft_Developer::debug-bar/' . $name . '.phtml'));
    }
}
