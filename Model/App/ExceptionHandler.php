<?php

declare(strict_types=1);

namespace Awesoft\Developer\Model\App;

use Awesoft\Developer\Model\Config;
use Awesoft\Developer\Model\Whoops\Handler\PrettyPageHandler;
use Awesoft\Developer\Model\Whoops\MagentoDataTable;
use Exception;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ExceptionHandler as AppExceptionHandler;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Throwable;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run;

class ExceptionHandler extends AppExceptionHandler
{
    /**
     * @param PrettyPageHandler $prettyPageHandler
     * @param Config $config
     * @param MagentoDataTable $magentoDataTable
     * @param EncryptorInterface $encryptor
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PrettyPageHandler $prettyPageHandler,
        private readonly Config $config,
        private readonly MagentoDataTable $magentoDataTable,
        EncryptorInterface $encryptor,
        Filesystem $filesystem,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($encryptor, $filesystem, $logger);
    }

    /**
     * @param Bootstrap $bootstrap
     * @param Exception $exception
     * @param Response $response
     * @param Request $request
     * @return bool
     */
    public function handle(Bootstrap $bootstrap, Exception $exception, Response $response, Request $request): bool
    {
        if ($bootstrap->getErrorCode() === Bootstrap::ERR_IS_INSTALLED || !$this->canUseWhoops()) {
            return parent::handle($bootstrap, $exception, $response, $request);
        }

        $this->logger->critical($exception);

        try {
            $handler = $this->isJsonRequest($request) ? $this->createJsonHandler() : $this->preparePrettyPageHandler($request);

            $whoops = new Run();
            $whoops->allowQuit(false);
            $whoops->writeToOutput(false);
            $whoops->pushHandler($handler);
            $body = $whoops->handleException($exception);
        } catch (Throwable $e) {
            $this->logger->error($e);

            return parent::handle($bootstrap, $exception, $response, $request);
        }

        $response->clearHeader('Location');
        $response->setHttpResponseCode($this->getStatusCode($exception));
        $response->setHeader('Content-Type', $handler instanceof JsonResponseHandler ? 'application/json' : 'text/html', true);
        $response->setBody($body);
        $response->sendResponse();

        return true;
    }

    /**
     * Exception codes are often not HTTP codes (e.g. PDO "23000"), only pass through valid error statuses
     *
     * @param Exception $exception
     * @return int
     */
    public function getStatusCode(Exception $exception): int
    {
        $code = $exception->getCode();

        return is_int($code) && $code >= 400 && $code <= 599 ? $code : 500;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isJsonRequest(Request $request): bool
    {
        return $request->isAjax() || str_contains((string)$request->getHeader('Accept'), 'application/json');
    }

    /**
     * @return bool
     */
    private function canUseWhoops(): bool
    {
        try {
            return $this->config->useWhoops();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return JsonResponseHandler
     */
    private function createJsonHandler(): JsonResponseHandler
    {
        $handler = new JsonResponseHandler();
        $handler->addTraceToOutput(true);

        return $handler;
    }

    /**
     * @param Request $request
     * @return HandlerInterface
     */
    private function preparePrettyPageHandler(Request $request): HandlerInterface
    {
        $handler = $this->prettyPageHandler;

        $editor = $this->config->getWhoopsEditor();
        $editorUrl = $this->config->getWhoopsEditorUrl();
        if ($editor === Config::EDITOR_CUSTOM) {
            if ($editorUrl) {
                $handler->addEditor(Config::EDITOR_CUSTOM, $editorUrl);
                $handler->setEditor(Config::EDITOR_CUSTOM);
            }
        } elseif ($editor && in_array($editor, $handler->getEditors(), true)) {
            $handler->setEditor($editor);
        }

        $handler->setPathMapping($this->config->getWhoopsPathMapping());
        $handler->setApplicationPaths([BP . '/app/code', BP . '/app/design']);
        $handler->addDataTable('Magento', $this->magentoDataTable->getData($request));
        $this->hideSecrets($handler, $request);

        return $handler;
    }

    /**
     * @param PrettyPageHandler $handler
     * @param Request $request
     * @return void
     */
    private function hideSecrets(PrettyPageHandler $handler, Request $request): void
    {
        $extraKeys = $this->config->getWhoopsHiddenKeys();
        $superGlobals = [
            '_GET' => $request->getQuery()->toArray(),
            '_POST' => $request->getPost()->toArray(),
            '_SERVER' => $request->getServer()->toArray(),
            '_ENV' => $request->getEnv()->toArray(),
        ];
        foreach ($superGlobals as $superGlobal => $values) {
            foreach (array_keys($values) as $key) {
                if (preg_match(Config::SECRET_KEY_PATTERN, (string)$key) || in_array($key, $extraKeys, true)) {
                    $handler->hideSuperglobalKey($superGlobal, $key);
                }
            }
        }

        // every cookie can carry a session id (PHPSESSID, admin), so none are shown
        foreach (explode(';', (string)$request->getServer('HTTP_COOKIE')) as $cookie) {
            $name = trim(explode('=', $cookie, 2)[0]);
            if ($name !== '') {
                $handler->hideSuperglobalKey('_COOKIE', $name);
            }
        }
    }
}
