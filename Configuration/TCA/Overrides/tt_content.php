<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerPlugin(
    'RubinEvents',
    'EventList',
    'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:plugin.rubinevents_eventlist',
    'ext-rubin-events-plugin',
    'plugins',
    'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:plugin.rubinevents_eventlist.description',
    'FILE:EXT:rubin_events/Configuration/FlexForms/EventList.xml'
);

ExtensionUtility::registerPlugin(
    'RubinEvents',
    'EventShow',
    'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:plugin.rubinevents_eventshow',
    'ext-rubin-events-plugin',
    'plugins',
    'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:plugin.rubinevents_eventshow.description',
    'FILE:EXT:rubin_events/Configuration/FlexForms/EventShow.xml'
);

ExtensionUtility::registerPlugin(
    'RubinEvents',
    'EventArchive',
    'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:plugin.rubinevents_eventarchive',
    'ext-rubin-events-plugin',
    'plugins',
    'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:plugin.rubinevents_eventarchive.description',
    'FILE:EXT:rubin_events/Configuration/FlexForms/EventArchive.xml'
);