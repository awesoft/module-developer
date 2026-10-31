<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\Config\Source;

use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\Whoops\Handler\PrettyPageHandler;
use Magento\Framework\Data\OptionSourceInterface;

class Editors implements OptionSourceInterface
{
    private const LABELS = [
        'sublime' => 'Sublime Text',
        'textmate' => 'TextMate',
        'emacs' => 'Emacs',
        'macvim' => 'MacVim',
        'phpstorm' => 'PhpStorm',
        'idea' => 'IntelliJ IDEA',
        'vscode' => 'VS Code',
        'atom' => 'Atom',
        'espresso' => 'Espresso',
        'netbeans' => 'NetBeans',
        'cursor' => 'Cursor',
        'xdebug' => 'Xdebug file link format',
    ];

    /**
     * @param PrettyPageHandler $prettyPageHandler
     */
    public function __construct(
        private readonly PrettyPageHandler $prettyPageHandler,
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- None --')]];
        foreach ($this->prettyPageHandler->getEditors() as $editor) {
            $options[] = ['value' => $editor, 'label' => self::LABELS[$editor] ?? ucfirst($editor)];
        }
        $options[] = ['value' => Config::EDITOR_CUSTOM, 'label' => __('Custom URL')];

        return $options;
    }
}
