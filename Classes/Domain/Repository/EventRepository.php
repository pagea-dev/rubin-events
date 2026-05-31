<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace PageaDev\RubinEvents\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

final class EventRepository extends Repository
{
    /**
     * Returns upcoming and today's events, sorted from earliest to latest.
     */
    public function findAllSorted(int $limit = 0): QueryResultInterface
    {
        $today = new \DateTime('today midnight');

        $query = $this->createQuery();
        $query->matching(
            $query->greaterThanOrEqual('eventStart', $today)
        );
        $query->setOrderings(['eventStart' => QueryInterface::ORDER_ASCENDING]);

        if ($limit > 0) {
            $query->setLimit($limit);
        }
        
        return $query->execute();
    }

    /**
     * Returns past events (before today), sorted from most recent to oldest.
     */
    public function findAllPast(int $limit = 0): QueryResultInterface
    {
        $today = new \DateTime('today midnight');

        $query = $this->createQuery();
        $query->matching(
            $query->lessThan('eventStart', $today)
        );
        $query->setOrderings(['eventStart' => QueryInterface::ORDER_DESCENDING]);

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $query->execute();
    }
}
