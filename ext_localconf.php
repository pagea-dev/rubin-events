<?php

declare(strict_types=1);

use PageaDev\RubinEvents\Controller\EventController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'RubinEvents',
    'EventList',
    [EventController::class => 'list'],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'RubinEvents',
    'EventShow',
    [EventController::class => 'show'],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'RubinEvents',
    'EventArchive',
    [EventController::class => 'archive'],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][] = [
    'nodeName' => 'rubinEventsMapPicker',
    'priority' => 40,
    'class' => \PageaDev\RubinEvents\Form\Element\MapPickerElement::class,
];
