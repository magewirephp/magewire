<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Console\Command;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\FileManager;
use Magewirephp\Symfony\Component\Console\Command\MagewireCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCompiledViews extends MagewireCommand
{
    public function __construct(
        private readonly FileManager $fileManager,
        string|null $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('compile:clear');
        $this->setDescription('Delete all compiled Magewire views from var/magewire/views.');

        $this->addOption('area', 'a', InputOption::VALUE_REQUIRED, 'Only clear compiled views for a single area (e.g. frontend or adminhtml).');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $area = $input->getOption('area');

        try {
            $path = $this->fileManager->getCompiledViewsPath() . ( $area === null ? '' : DIRECTORY_SEPARATOR . $area );

            if (! $this->fileManager->system()->exists($path)) {
                $output->writeln(sprintf('<info>No compiled Magewire views found%s. Nothing to clear.</info>', $area === null ? '' : sprintf(' for area "%s"', $area)));

                return self::SUCCESS;
            }

            $this->fileManager->clear($area);

            $output->writeln(sprintf('<info>Cleared %s compiled Magewire views from %s.</info>', $area === null ? 'all' : sprintf('the "%s" area', $area), $path));
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
