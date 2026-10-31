<?php

declare(strict_types=1);

namespace Awesoft\Developer\Console\Command;

use Awesoft\Developer\Model\Config;
use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ToggleCommand extends Command
{
    private const ARG_FEATURE = 'feature';
    private const ARG_STATE = 'state';

    private const FEATURES = [
        'whoops' => Config::PATH_USE_WHOOPS,
        'debugbar' => Config::PATH_DEBUG_BAR,
    ];

    private const STATES = ['on' => 1, 'off' => 0];

    /**
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly TypeListInterface $cacheTypeList,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('awesoft:developer:toggle')
            ->setDescription('Turn Whoops or the developer debug bar on or off')
            ->addArgument(self::ARG_FEATURE, InputArgument::REQUIRED, implode('|', array_keys(self::FEATURES)))
            ->addArgument(self::ARG_STATE, InputArgument::REQUIRED, implode('|', array_keys(self::STATES)));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $feature = strtolower((string)$input->getArgument(self::ARG_FEATURE));
        $state = strtolower((string)$input->getArgument(self::ARG_STATE));

        if (!isset(self::FEATURES[$feature], self::STATES[$state])) {
            $output->writeln(sprintf(
                '<error>Usage: %s <%s> <%s></error>',
                $this->getName(),
                implode('|', array_keys(self::FEATURES)),
                implode('|', array_keys(self::STATES))
            ));

            return Cli::RETURN_FAILURE;
        }

        $this->configWriter->save(self::FEATURES[$feature], self::STATES[$state]);
        $this->cacheTypeList->cleanType(ConfigCache::TYPE_IDENTIFIER);

        $output->writeln(sprintf('<info>%s turned %s.</info>', $feature, $state));
        if ($feature === 'debugbar' && $state === 'on') {
            $output->writeln('Also enable it per area under Stores > Configuration > Advanced > Developer.');
        }

        return Cli::RETURN_SUCCESS;
    }
}
