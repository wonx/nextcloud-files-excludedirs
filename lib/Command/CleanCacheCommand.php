<?php

namespace OCA\Files_ExcludeDirs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IConfig;
use OCP\IDBConnection;

class CleanCacheCommand extends Command {
    /** @var IConfig */
    private $config;
    /** @var IDBConnection */
    private $db;

    public function __construct(IConfig $config, IDBConnection $db) {
        parent::__construct();
        $this->config = $config;
        $this->db = $db;
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
            // Safety check: Prevent accidental wildcards from wiping the whole DB
            if (trim($pattern) === '' || $pattern === '*') {
                continue;
            }

            $output->writeln("Evaluating pattern: <comment>$pattern</comment>");

            if ($dryRun) {
                // Safe read-only preview of what would be deleted
                $qb = $this->db->getQueryBuilder();
                $qb->select('path')
                   ->from('filecache')
                   ->where($qb->expr()->like('path', $qb->createNamedParameter('%' . $pattern . '%')));

                $result = $qb->executeQuery();
                
                $count = 0;
                $limit = 50; // Prevents terminal flooding
                
                while ($row = $result->fetchAssociative()) {
                    if ($count < $limit) {
                        $output->writeln("  - <comment>" . $row['path'] . "</comment>");
                    }
                    $count++;
                }
                
                if ($count > $limit) {
                    $remaining = $count - $limit;
                    $output->writeln("    ... and <comment>$remaining</comment> more paths.");
                }
                
                $output->writeln("<info>Found $count matching cached file entries.</info>");
                $output->writeln('');
            } else {
                // Actual delete logic
                $qb = $this->db->getQueryBuilder();
                $qb->delete('filecache')
                   ->where($qb->expr()->like('path', $qb->createNamedParameter('%' . $pattern . '%')));
                
                $rowsAffected = $qb->executeStatement();
                $output->writeln("<info>Deleted $rowsAffected cached file entries.</info>");
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