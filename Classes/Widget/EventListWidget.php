<?php

declare(strict_types=1);

namespace PageaDev\RubinEvents\Widget;

use PageaDev\RubinEvents\Domain\Repository\EventRepository;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;

use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

final class EventListWidget implements WidgetInterface
{
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly EventRepository $eventRepository,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly ButtonProviderInterface $buttonProvider,
        private readonly array $options = []
    ) {}

    /**
     * Renders the widget content via Fluid template
     */
    public function renderWidgetContent(): string
    {
        $query = $this->eventRepository->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings(['eventStart' => QueryInterface::ORDER_ASCENDING]);

        $now    = new \DateTime();
        $past   = [];
        $future = [];

        foreach ($query->execute() as $event) {
            if ($event->getEventStart() && $event->getEventStart() < $now) {
                $past[] = $event;
            } else {
                $future[] = $event;
            }
        }

        // keep only last 3 past events (closest to now)
        $past = array_slice($past, -3);

        // find next upcoming event for bold marking
        $nextEvent = $future[0] ?? null;

        $view = $this->viewFactory->create(
            new ViewFactoryData(
                templateRootPaths: ['EXT:rubin_events/Resources/Private/Templates/'],
                partialRootPaths: [
                    'EXT:dashboard/Resources/Private/Partials/',
                    'EXT:rubin_events/Resources/Private/Partials/',
                ],
                layoutRootPaths: ['EXT:dashboard/Resources/Private/Layouts/'],
            )
        );

        $view->assign('button', $this->buttonProvider);
        $view->assign('events', array_merge($past, $future));
        $view->assign('nextEvent', $nextEvent);

        return $view->render('Widget/EventList');
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getConfiguration(): WidgetConfigurationInterface
    {
        return $this->configuration;
    }
}