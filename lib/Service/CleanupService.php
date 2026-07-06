<?php
namespace OCA\Files_ExcludeDirs\Service;

use OCP\IDBConnection;

class CleanupService {
    private IDBConnection $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    public function preview(array $patterns): array {
        $results = ['count' => 0, 'paths' => []];
        $limit = 50;

        foreach ($patterns as $pattern) {
            if (trim($pattern) === '' || $pattern === '*') continue;

            // Fix: Translate Glob wildcard '*' to SQL LIKE wildcard '%'
            $sqlPattern = str_replace('*', '%', $pattern);

            $qb = $this->db->getQueryBuilder();
            $qb->select('path')
               ->from('filecache')
               ->where(
                   $qb->expr()->orX(
                       $qb->expr()->like('path', $qb->createNamedParameter($sqlPattern)),
                       $qb->expr()->like('path', $qb->createNamedParameter($sqlPattern . '/%')),
                       $qb->expr()->like('path', $qb->createNamedParameter('%/' . $sqlPattern)),
                       $qb->expr()->like('path', $qb->createNamedParameter('%/' . $sqlPattern . '/%'))
                   )
               );

            $result = $qb->executeQuery();
            // Support both Nextcloud 34 (fetchAssociative) and older versions like NC 32 (fetch)
            while ($row = method_exists($result, 'fetchAssociative') ? $result->fetchAssociative() : $result->fetch()) {
                if ($results['count'] < $limit) {
                    $results['paths'][] = $row['path'];
                }
                $results['count']++;
            }
        }
        return $results;
    }

    public function cleanup(array $patterns): int {
        $deleted = 0;
        foreach ($patterns as $pattern) {
            if (trim($pattern) === '' || $pattern === '*') continue;

            // Fix: Translate Glob wildcard '*' to SQL LIKE wildcard '%'
            $sqlPattern = str_replace('*', '%', $pattern);

            $qb = $this->db->getQueryBuilder();
            $qb->delete('filecache')
               ->where(
                   $qb->expr()->orX(
                       $qb->expr()->like('path', $qb->createNamedParameter($sqlPattern)),
                       $qb->expr()->like('path', $qb->createNamedParameter($sqlPattern . '/%')),
                       $qb->expr()->like('path', $qb->createNamedParameter('%/' . $sqlPattern)),
                       $qb->expr()->like('path', $qb->createNamedParameter('%/' . $sqlPattern . '/%'))
                   )
               );
            
            $deleted += $qb->executeStatement();
        }
        return $deleted;
    }
}