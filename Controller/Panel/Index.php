<?php

declare(strict_types=1);

namespace Awesoft\Developer\Controller\Panel;

use Awesoft\Developer\Block\DebugBar;
use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\DebugBar\Storage;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutInterface;

/**
 * Renders the panels of a stored AJAX request for the debug bar
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param RawFactory $rawFactory
     * @param LayoutInterface $layout
     * @param Config $config
     * @param Storage $storage
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RawFactory $rawFactory,
        private readonly LayoutInterface $layout,
        private readonly Config $config,
        private readonly Storage $storage,
    ) {
    }

    /**
     * @return Raw
     */
    public function execute(): Raw
    {
        $result = $this->rawFactory->create();
        $result->setHeader('Cache-Control', 'no-store', true);
        $result->setHeader('X-Robots-Tag', 'noindex', true);

        $profile = $this->config->isDebugBarAllowed()
            ? $this->storage->load((string)$this->request->getParam('token'))
            : null;
        if (!$profile) {
            return $result->setHttpResponseCode(404)->setContents('');
        }

        /** @var DebugBar $block */
        $block = $this->layout->createBlock(DebugBar::class);
        $block->setPanels($profile['panels'] ?? [])->setTemplate('Awesoft_Developer::debug-bar/fragment.phtml');

        return $result->setContents($block->toHtml());
    }
}
