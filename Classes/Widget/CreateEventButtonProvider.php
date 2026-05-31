<?php

declare(strict_types=1);

namespace PageaDev\RubinEvents\Widget;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

final class CreateEventButtonProvider implements ButtonProviderInterface
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function getTitle(): string
    {
        return LocalizationUtility::translate('widget.createButton.title', 'rubin_events') ?? 'Create new event';
    }

    /**
     * Generates backend URL for creating a new event record
     */
    public function getLink(): string
    {
        $conf = $this->extensionConfiguration->get('rubin_events');
        $pid  = (int)($conf['storagePid'] ?? 0);

        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit'      => ['tx_rubinevents_domain_model_event' => [$pid => 'new']],
            'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $pid]),
        ]);
    }

    public function getTarget(): string
    {
        return '';
    }
}