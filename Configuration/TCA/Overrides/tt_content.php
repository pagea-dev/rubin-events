<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

$plugins = [
    'EventList' => 'EventList',
    'EventShow' => 'EventShow',
    'EventArchive' => 'EventArchive',
];

foreach ($plugins as $pluginName => $flexFormName) {
    $languageFile = 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:plugin.rubinevents_' . strtolower($pluginName);

    $contentTypeName = ExtensionUtility::registerPlugin(
        'RubinEvents',
        $pluginName,
        $languageFile,
        'ext-rubin-events-plugin',
        'plugins',
        $languageFile . '.description',
    );

    // Register the FlexForm data structure for this CType. Passing it as 7th argument of
    // registerPlugin() only works from TYPO3 v14 on, so it is wired up explicitly here.
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:rubin_events/Configuration/FlexForms/' . $flexFormName . '.xml',
        $contentTypeName
    );

    // Show the FlexForm in the content element form
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:plugin, pi_flexform',
        $contentTypeName,
        'after:palette:headers'
    );
}
