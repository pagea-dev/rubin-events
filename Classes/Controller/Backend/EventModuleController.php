<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace PageaDev\RubinEvents\Controller\Backend;

use PageaDev\RubinEvents\Domain\Model\Event;
use PageaDev\RubinEvents\Domain\Repository\EventRepository;
use PageaDev\RubinEvents\Service\ConfigurationCheck;
use PageaDev\RubinEvents\Service\ExampleContentImporter;
use PageaDev\RubinEvents\Service\PageTreeImporter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Web > Events: overview of all event records, split into upcoming and past.
 *
 * Deliberately a plain backend controller instead of an Extbase one: the module APIs used here
 * (ModuleTemplateFactory, ButtonBar, the route based module registration) are identical in
 * TYPO3 v13 and v14, so the module does not need version switches.
 */
#[AsController]
final class EventModuleController
{
    private const TABLE = 'tx_rubinevents_domain_model_event';
    private const LL = 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_mod.xlf:';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly EventRepository $eventRepository,
        private readonly UriBuilder $uriBuilder,
        private readonly IconFactory $iconFactory,
        private readonly ConfigurationCheck $configurationCheck,
        private readonly FlashMessageService $flashMessageService,
        private readonly ExampleContentImporter $exampleContentImporter,
        private readonly PageTreeImporter $pageTreeImporter,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('web_rubinevents');

        // Writing actions redirect back to the plain module URL afterwards, so a reload of the
        // result page cannot repeat the write
        $action = (string)($request->getQueryParams()['action'] ?? '');

        if ($action === 'importExamples' || $action === 'importPageTree') {
            $action === 'importExamples' ? $this->importExamples() : $this->importPageTree();

            return new RedirectResponse($returnUrl);
        }

        $view = $this->moduleTemplateFactory->create($request);

        [$upcoming, $past] = $this->collectEvents();

        $this->addDocHeaderButtons($view->getDocHeaderComponent()->getButtonBar(), $returnUrl);

        $view->setTitle($this->getLanguageService()->sL(self::LL . 'mlang_tabs_tab'));

        return $view->assignMultiple([
            'upcoming' => $this->decorate($upcoming, $returnUrl),
            'past' => $this->decorate($past, $returnUrl),
            'storagePid' => $this->configurationCheck->storagePid(),
            'configuredStoragePid' => $this->configurationCheck->configuredStoragePid(),
            'newEventUrl' => $this->buildNewRecordUrl($returnUrl),
            'configuration' => $this->configurationCheck->settings(),
            'configurationState' => $this->configurationCheck->state(),
            // Shown in the module footer next to the "report an issue" button, where it is the one
            // detail every bug report needs
            'extensionVersion' => $this->extensionVersion(),
        ])->renderResponse('Backend/EventList');
    }

    /**
     * The version the extension declares about itself, for the module footer.
     *
     * Deliberately read from ext_emconf.php rather than through
     * ExtensionManagementUtility::getExtensionVersion(): that one reports the Composer package
     * version, which is "dev-main" in a path-repository checkout — the way this extension is
     * developed. ext_emconf.php is the single source for the version and reads the same in every
     * installation type.
     */
    private function extensionVersion(): string
    {
        $_EXTKEY = 'rubin_events';
        $EM_CONF = [];

        require ExtensionManagementUtility::extPath($_EXTKEY) . 'ext_emconf.php';

        return (string)($EM_CONF[$_EXTKEY]['version'] ?? '');
    }

    /**
     * All events regardless of storage folder, split at "now".
     *
     * @return array{0: list<Event>, 1: list<Event>}
     */
    private function collectEvents(): array
    {
        $query = $this->eventRepository->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings(['eventStart' => QueryInterface::ORDER_ASCENDING]);

        $now = new \DateTime();
        $upcoming = [];
        $past = [];

        foreach ($query->execute() as $event) {
            $start = $event->getEventStart();

            if ($start !== null && $start < $now) {
                $past[] = $event;
            } else {
                $upcoming[] = $event;
            }
        }

        // Most recent first, the older an event gets the less interesting it is here
        return [$upcoming, array_reverse($past)];
    }

    /**
     * Pairs each event with its edit URL, so the template stays free of URL building.
     *
     * @param list<Event> $events
     * @return list<array{event: Event, editUrl: string}>
     */
    private function decorate(array $events, string $returnUrl): array
    {
        $rows = [];

        foreach ($events as $event) {
            $rows[] = [
                'event' => $event,
                'editUrl' => (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [self::TABLE => [$event->getUid() => 'edit']],
                    'returnUrl' => $returnUrl,
                ]),
            ];
        }

        return $rows;
    }

    private function addDocHeaderButtons(ButtonBar $buttonBar, string $returnUrl): void
    {
        $languageService = $this->getLanguageService();
        $hasStorage = $this->configurationCheck->storagePid() > 0;

        if ($hasStorage) {
            $newButton = $buttonBar->makeLinkButton()
                ->setHref($this->buildNewRecordUrl($returnUrl))
                ->setTitle($languageService->sL(self::LL . 'module.button.new'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));

            $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT);
        }

        // Top right, next to the shortcut button. Both imports are independent of the configured
        // storage PID: they bring their folder along, which is exactly what makes them useful on a
        // fresh installation where nothing is configured yet.
        $pageTreeButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_rubinevents', ['action' => 'importPageTree']))
            ->setTitle($languageService->sL(self::LL . 'module.button.importPageTree'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-pagetree', IconSize::SMALL));

        $buttonBar->addButton($pageTreeButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $importButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_rubinevents', ['action' => 'importExamples']))
            ->setTitle($languageService->sL(self::LL . 'module.button.importExamples'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-download', IconSize::SMALL));

        $buttonBar->addButton($importButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_rubinevents')
            ->setDisplayName($languageService->sL(self::LL . 'mlang_tabs_tab'));

        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Runs the example content import: a storage folder in the page tree, the contacts with their
     * photos and the example events inside it. The content itself lives in
     * Resources/Private/ExampleContent/dump.sql.
     */
    private function importExamples(): void
    {
        $existing = $this->exampleContentImporter->findExistingFolder();

        if ($existing > 0) {
            $this->addFlashMessage(
                'module.import.alreadyImported',
                ContextualFeedbackSeverity::INFO,
                [ExampleContentImporter::FOLDER_TITLE, $existing]
            );

            return;
        }

        try {
            $result = $this->exampleContentImporter->import();
        } catch (\Throwable $exception) {
            $this->addFlashMessage(
                'module.import.failed',
                ContextualFeedbackSeverity::ERROR,
                [$exception->getMessage()]
            );

            return;
        }

        $this->addFlashMessage(
            'module.import.done',
            ContextualFeedbackSeverity::OK,
            [$result['events'], $result['contacts'], ExampleContentImporter::FOLDER_TITLE, $result['pageUid']]
        );
    }

    /**
     * Runs the page structure import and fills the storage folder it created with the example
     * content, so what comes out is a setup that already works instead of empty pages.
     *
     * The structure is the part that cannot be repeated, so a failing content import is reported
     * as a warning rather than swallowing the fact that the pages are there.
     */
    private function importPageTree(): void
    {
        $existing = $this->pageTreeImporter->findExistingRoot();

        if ($existing > 0) {
            $this->addFlashMessage(
                'module.pagetree.alreadyImported',
                ContextualFeedbackSeverity::INFO,
                [$existing],
                'module.button.importPageTree'
            );

            return;
        }

        try {
            $structure = $this->pageTreeImporter->import($this->backendLanguage());
        } catch (\Throwable $exception) {
            $this->addFlashMessage(
                'module.pagetree.failed',
                ContextualFeedbackSeverity::ERROR,
                [$exception->getMessage()],
                'module.button.importPageTree'
            );

            return;
        }

        try {
            $content = $this->exampleContentImporter->import($structure['storagePid']);
        } catch (\Throwable $exception) {
            $this->addFlashMessage(
                'module.pagetree.contentFailed',
                ContextualFeedbackSeverity::WARNING,
                [$structure['root'], $exception->getMessage()],
                'module.button.importPageTree'
            );

            return;
        }

        $this->addFlashMessage(
            'module.pagetree.done',
            ContextualFeedbackSeverity::OK,
            [
                $structure['pages'],
                $structure['content'],
                $structure['root'],
                $content['events'],
                $content['contacts'],
                $structure['storagePid'],
            ],
            'module.button.importPageTree'
        );
    }

    /**
     * @param list<string|int> $arguments
     */
    private function addFlashMessage(
        string $key,
        ContextualFeedbackSeverity $severity,
        array $arguments = [],
        string $titleKey = 'module.button.importExamples',
    ): void {
        $message = $this->getLanguageService()->sL(self::LL . $key);

        if ($arguments !== []) {
            $message = vsprintf($message, $arguments);
        }

        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(
            new FlashMessage(
                $message,
                $this->getLanguageService()->sL(self::LL . $titleKey),
                $severity,
                true
            )
        );
    }

    private function buildNewRecordUrl(string $returnUrl): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [self::TABLE => [$this->configurationCheck->storagePid() => 'new']],
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * Language the backend user works in, used to pick the page structure dump.
     */
    private function backendLanguage(): ?string
    {
        return $this->getLanguageService()->getLocale()?->getLanguageCode();
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
