<?php

declare(strict_types=1);

use PageaDev\RubinEvents\Controller\Backend\EventModuleController;

/**
 * Backend module of EXT:rubin_events.
 *
 * Uses the route based registration (`routes` instead of `controllerActions`), which keeps the
 * controller a plain PSR-15 style class instead of an Extbase one. That is the registration form
 * TYPO3 v13 and v14 have in common.
 */
return [
    'web_rubinevents' => [
        'parent' => 'web',
        'position' => ['after' => 'web_list'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/rubinevents',
        'iconIdentifier' => 'ext-rubin-events-plugin',
        'labels' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => EventModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
