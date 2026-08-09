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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use PageaDev\RubinEvents\Domain\Repository\EventRepository;
use PageaDev\RubinEvents\Domain\Model\Event;

class EventController extends ActionController
{

    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ExtensionConfiguration $extensionConfiguration
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
        $this->view->assign('listPid', $this->resolveListPid());
        $this->view->assign('loadSwiper', $this->shouldLoadSwiper());
        return $this->htmlResponse();
    }

    /**
     * Whether the bundled Swiper element should be shipped with the slider list style. Sites that
     * already provide Swiper themselves switch this off in the extension configuration.
     */
    private function shouldLoadSwiper(): bool
    {
        try {
            $configuration = $this->extensionConfiguration->get('rubin_events');
        } catch (\Throwable) {
            return false;
        }

        return (bool)($configuration['useSwiper'] ?? false);
    }

    /**
     * List page handed over to the detail view for its back button. Falls back to the page
     * this plugin is rendered on, so the detail view returns to wherever the link was clicked.
     */
    private function resolveListPid(): int
    {
        $listPid = (int)($this->settings['pidList'] ?? 0);

        if ($listPid > 0) {
            return $listPid;
        }

        return (int)($this->request->getAttribute('frontend.page.information')?->getId() ?? 0);
    }




    
    public function initializeShowAction(): void
    {
        if ($this->arguments->hasArgument('event')) {
            $this->arguments->getArgument('event')->setRequired(false);
        }
    }

    public function showAction(?Event $event = null, int $pidList = 0): ResponseInterface
    {

        $storagePid = (int)($this->settings['storagePid'] ?? 0);

        if ($storagePid > 0) {
            $querySettings = $this->eventRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds([$storagePid]);
            $this->eventRepository->setDefaultQuerySettings($querySettings);
        }

        // The linking list plugin hands over the page to return to, so one detail page can
        // serve several lists. Without it the plugin's own list page is used.
        $listPid = $pidList > 0 ? $pidList : (int)($this->settings['pidList'] ?? 0);

        if ($event === null) {
            return $this->redirectToUri(
                $this->uriBuilder->reset()->setTargetPageUid($listPid > 0 ? $listPid : 1)->build()
            );
        }

        $this->view->assign('event', $event);
        $this->view->assign('listPid', $listPid);
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
        $this->view->assign('listPid', $this->resolveListPid());
        return $this->htmlResponse();
    }
}