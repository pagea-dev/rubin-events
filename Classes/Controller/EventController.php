<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace PageaDev\RubinEvents\Controller;

use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use PageaDev\RubinEvents\Domain\Repository\EventRepository;
use PageaDev\RubinEvents\Domain\Model\Event;

class EventController extends ActionController
{

    public function __construct(
        private readonly EventRepository $eventRepository
    ) {}

    public function listAction(): ResponseInterface
    {
        $storagePid = (int)($this->settings['storagePid'] ?? 0);

        if ($storagePid > 0) {
            $querySettings = $this->eventRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds([$storagePid]);
            $this->eventRepository->setDefaultQuerySettings($querySettings);
        }

        $limit = (int)($this->settings['limit'] ?? 0);
        $this->view->assign('events', $this->eventRepository->findAllSorted($limit));
        return $this->htmlResponse();
    }




    
    public function initializeShowAction(): void
    {
        if ($this->arguments->hasArgument('event')) {
            $this->arguments->getArgument('event')->setRequired(false);
        }
    }

    public function showAction(?Event $event = null): ResponseInterface
    {

        $storagePid = (int)($this->settings['storagePid'] ?? 0);

        if ($storagePid > 0) {
            $querySettings = $this->eventRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds([$storagePid]);
            $this->eventRepository->setDefaultQuerySettings($querySettings);
        }

        if ($event === null) {
            $listPid = (int)($this->settings['pidList'] ?? 1);

            return $this->redirectToUri(
                $this->uriBuilder->reset()->setTargetPageUid($listPid)->build()
            );

            
        }

        $this->view->assign('event', $event);
        return $this->htmlResponse();
    }

    public function archiveAction(): ResponseInterface
    {
        $storagePid = (int)($this->settings['storagePid'] ?? 0);

        if ($storagePid > 0) {
            $querySettings = $this->eventRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds([$storagePid]);
            $this->eventRepository->setDefaultQuerySettings($querySettings);
        }

        $limit = (int)($this->settings['limit'] ?? 0);
        $this->view->assign('events', $this->eventRepository->findAllPast($limit));
        return $this->htmlResponse();
    }
}