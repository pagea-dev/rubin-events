<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
return [
    'ext-rubin-events-plugin' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:rubin_events/Resources/Public/Icons/Plugin.svg',
    ],
    // Swapped in for the module menu entry by PageaDev\RubinEvents\Backend\ModuleIndicator while
    // the extension configuration is not usable
    'ext-rubin-events-plugin-warning' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:rubin_events/Resources/Public/Icons/PluginWarning.svg',
    ],
];
