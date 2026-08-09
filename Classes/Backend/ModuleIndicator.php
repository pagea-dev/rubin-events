<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace PageaDev\RubinEvents\Backend;

use PageaDev\RubinEvents\Service\ConfigurationCheck;
use TYPO3\CMS\Backend\Module\BeforeModuleCreationEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Marks the Web > Events entry in the module menu while the extension configuration is not usable.
 *
 * Without this the module looks perfectly normal from the outside and you only find out that
 * something is off after opening it — which is no help to whoever has to notice in the first place.
 *
 * Both halves of the menu entry carry the marker, because only one of them is ever visible: the
 * icon when the module menu is collapsed, the title when it is not. The hover text names the state
 * so it is clear whether some or all settings are the problem.
 */
#[AsEventListener(identifier: 'rubin-events/module-indicator')]
final class ModuleIndicator
{
    private const MODULE = 'web_rubinevents';
    private const LL = 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_mod.xlf:';

    public function __construct(
        private readonly ConfigurationCheck $configurationCheck,
    ) {}

    public function __invoke(BeforeModuleCreationEvent $event): void
    {
        if ($event->getIdentifier() !== self::MODULE) {
            return;
        }

        $state = $this->configurationCheck->state();

        if ($state === ConfigurationCheck::STATE_OK) {
            return;
        }

        $event->setConfigurationValue('iconIdentifier', 'ext-rubin-events-plugin-warning');

        // Replacing "labels" means spelling out all three of them: the string form resolves the
        // whole set from one file, the array form does not fall back to it
        $event->setConfigurationValue('labels', [
            'title' => self::LL . 'mlang_tabs_tab.misconfigured',
            'description' => self::LL . 'mlang_labels_tabdescr',
            'shortDescription' => self::LL . 'module.config.state.' . $state,
        ]);
    }
}
