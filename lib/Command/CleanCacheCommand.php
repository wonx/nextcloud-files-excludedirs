<?php

namespace OCA\Files_ExcludeDirs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IConfig;
use OCP\IDBConnection;

class CleanCacheCommand extends Command {
    /** @var IConfig */
    private $config;
    /** @var IDBConnection */
    private $db;

    // Nextcloud automatically injects these two dependencies into the constructor!
    public function __construct(IConfig $config, IDBConnection $db) {
        parent::__construct();
        $this->config = $config;
        $this->db = $db;
    }

    protected function configure(): void {
        $this->setName('files_excludedirs:clean-cache')
             ->setDescription('Clean excluded paths from the database filecache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $excludePatterns = json_decode(
            $this->config->getAppValue('files_excludedirs', 'exclude', '[".snapshot"]'),
            true
        );

        if (empty($excludePatterns)) {
            $output->writeln('<info>No patterns found to clean.</info>');
            return Command::SUCCESS;
        }

        foreach ($excludePatterns as $pattern) {
            // Safety check: Prevent accidental wildcards from wiping the whole DB
            if (trim($pattern) === '' || $pattern === '*') {
                continue;
            }

            $output->writeln("Cleaning cache entries matching pattern: <comment>$pattern</comment>");

            // Safe database execution using Doctrine DBAL Query Builder
            $qb = $this->db->getQueryBuilder();
            $qb->delete('filecache')
               ->where($qb->expr()->like('path', $qb->createNamedParameter('%' . $pattern . '%')));
            
            $rowsAffected = $qb->executeStatement();
            $output->writeln("<info>Deleted $rowsAffected cached file entries.</info>");
        }

        $output->writeln('<info>Database cleanup finished!</info>');
        return Command::SUCCESS;
    }
}