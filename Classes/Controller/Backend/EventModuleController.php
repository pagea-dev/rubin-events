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
use PageaDev\RubinEvents\Service\ExampleContentImporter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly FlashMessageService $flashMessageService,
        private readonly ExampleContentImporter $exampleContentImporter,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('web_rubinevents');

        // Writing actions redirect back to the plain module URL afterwards, so a reload of the
        // result page cannot repeat the write
        if (($request->getQueryParams()['action'] ?? '') === 'importExamples') {
            $this->importExamples();

            return new RedirectResponse($returnUrl);
        }

        $view = $this->moduleTemplateFactory->create($request);

        [$upcoming, $past] = $this->collectEvents();
        $configuration = $this->checkConfiguration();

        $this->addDocHeaderButtons($view->getDocHeaderComponent()->getButtonBar(), $returnUrl);

        $view->setTitle($this->getLanguageService()->sL(self::LL . 'mlang_tabs_tab'));

        return $view->assignMultiple([
            'upcoming' => $this->decorate($upcoming, $returnUrl),
            'past' => $this->decorate($past, $returnUrl),
            'storagePid' => $this->getStoragePid(),
            'configuredStoragePid' => $this->getConfiguredStoragePid(),
            'newEventUrl' => $this->buildNewRecordUrl($returnUrl),
            'configuration' => $configuration,
            'configurationState' => $this->configurationState($configuration),
        ])->renderResponse('Backend/EventList');
    }

    /**
     * State of every extension setting that can actually be wrong.
     *
     * useSwiper is left out on purpose: a checkbox is either on or off, there is no invalid value
     * to report, and counting it would make the "everything is wrong" case unreachable.
     *
     * @return list<array{key: string, value: string, valid: bool}>
     */
    private function checkConfiguration(): array
    {
        try {
            $settings = $this->extensionConfiguration->get('rubin_events');
        } catch (\Throwable) {
            $settings = [];
        }

        $zoom = (string)($settings['defaultZoom'] ?? '');
        $lat = (string)($settings['defaultLat'] ?? '');
        $lon = (string)($settings['defaultLon'] ?? '');
        $storagePid = (string)($settings['storagePid'] ?? '');

        return [
            [
                'key' => 'storagePid',
                'value' => $storagePid,
                // Not just "is a number": a PID pointing at a page that does not exist is the
                // failure this module ran into before, so the page has to resolve
                'valid' => $this->getStoragePid() > 0,
            ],
            [
                'key' => 'defaultZoom',
                'value' => $zoom,
                'valid' => ctype_digit($zoom) && (int)$zoom >= 1 && (int)$zoom <= 18,
            ],
            [
                'key' => 'defaultLat',
                'value' => $lat,
                'valid' => is_numeric($lat) && (float)$lat >= -90 && (float)$lat <= 90,
            ],
            [
                'key' => 'defaultLon',
                'value' => $lon,
                'valid' => is_numeric($lon) && (float)$lon >= -180 && (float)$lon <= 180,
            ],
        ];
    }

    /**
     * Infobox state for the settings indicator: nothing wrong is green, everything wrong is red,
     * anything in between yellow.
     *
     * @param list<array{key: string, value: string, valid: bool}> $configuration
     */
    private function configurationState(array $configuration): int
    {
        $invalid = count(array_filter($configuration, static fn(array $setting): bool => !$setting['valid']));

        if ($invalid === 0) {
            return 0; // ok / green
        }

        return $invalid === count($configuration) ? 2 : 1; // error / red, warning / yellow
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
        $hasStorage = $this->getStoragePid() > 0;

        if ($hasStorage) {
            $newButton = $buttonBar->makeLinkButton()
                ->setHref($this->buildNewRecordUrl($returnUrl))
                ->setTitle($languageService->sL(self::LL . 'module.button.new'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));

            $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT);
        }

        // Top right, next to the shortcut button. Independent of the configured storage PID: the
        // import brings its own folder along, which is exactly what makes it useful on a fresh
        // installation where nothing is configured yet.
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
     * @param list<string|int> $arguments
     */
    private function addFlashMessage(string $key, ContextualFeedbackSeverity $severity, array $arguments = []): void
    {
        $message = $this->getLanguageService()->sL(self::LL . $key);

        if ($arguments !== []) {
            $message = vsprintf($message, $arguments);
        }

        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(
            new FlashMessage(
                $message,
                $this->getLanguageService()->sL(self::LL . 'module.button.importExamples'),
                $severity,
                true
            )
        );
    }

    private function buildNewRecordUrl(string $returnUrl): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [self::TABLE => [$this->getStoragePid() => 'new']],
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * Raw storage PID from the extension configuration, without checking whether it resolves.
     */
    private function getConfiguredStoragePid(): int
    {
        try {
            $configuration = $this->extensionConfiguration->get('rubin_events');
        } catch (\Throwable) {
            return 0;
        }

        return (int)($configuration['storagePid'] ?? 0);
    }

    /**
     * Folder new records are created in. Returns 0 when the configured page does not exist, so the
     * module never offers a "new record" link that FormEngine would reject afterwards.
     */
    private function getStoragePid(): int
    {
        $storagePid = $this->getConfiguredStoragePid();

        if ($storagePid <= 0) {
            return 0;
        }

        return BackendUtility::getRecord('pages', $storagePid, 'uid') !== null ? $storagePid : 0;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
