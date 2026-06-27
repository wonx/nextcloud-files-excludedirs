<?php

namespace OCA\Files_ExcludeDirs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IConfig;
use OCA\Files_ExcludeDirs\Service\CleanupService;

class CleanCacheCommand extends Command {
    /** @var IConfig */
    private $config;
    /** @var CleanupService */
    private $cleanupService;

    public function __construct(IConfig $config, CleanupService $cleanupService) {
        parent::__construct();
        $this->config = $config;
        $this->cleanupService = $cleanupService;
    }

    protected function configure(): void {
        $this->setName('files_excludedirs:clean-cache')
             ->setDescription('Clean excluded paths from the database filecache')
             ->addOption(
                 'dry-run',
                 'd',
                 InputOption::VALUE_NONE,
                 'List affected files and folders without deleting them'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $dryRun = $input->getOption('dry-run');

        $excludePatterns = json_decode(
            $this->config->getAppValue('files_excludedirs', 'exclude', '[".snapshot"]'),
            true
        );

        if (empty($excludePatterns)) {
            $output->writeln('<info>No patterns found to clean.</info>');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $output->writeln('<comment>Executing in DRY-RUN mode. No changes will be made to the database.</comment>');
            $output->writeln('');
        }

        foreach ($excludePatterns as $pattern) {
            if (trim($pattern) === '' || $pattern === '*') {
                continue;
            }

            $output->writeln("Evaluating pattern: <comment>$pattern</comment>");

            if ($dryRun) {
                $results = $this->cleanupService->preview([$pattern]);
                $limit = 50;
                
                foreach ($results['paths'] as $path) {
                    $output->writeln("  - <comment>" . $path . "</comment>");
                }
                
                if ($results['count'] > $limit) {
                    $remaining = $results['count'] - $limit;
                    $output->writeln("    ... and <comment>$remaining</comment> more paths.");
                }
                
                $output->writeln("<info>Found {$results['count']} matching cached file entries.</info>");
                $output->writeln('');
            } else {
                $deleted = $this->cleanupService->cleanup([$pattern]);
                $output->writeln("<info>Deleted $deleted cached file entries.</info>");
                $output->writeln('');
            }
        }

        if ($dryRun) {
            $output->writeln('<info>Dry-run scan complete!</info>');
        } else {
            $output->writeln('<info>Database cleanup finished!</info>');
        }
        
        return Command::SUCCESS;
    }
}