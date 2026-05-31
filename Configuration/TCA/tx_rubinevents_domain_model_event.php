<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'typeicon_classes' => [
            'default' => 'ext-rubin-events-plugin',
        ],
    ],
    'types' => [
        [
            'showitem' => '
                --div--;core.form.tabs:general,
                    event_start, event_end, title, teaser, description, location, map_location, creator, contacts,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    hidden,--palette--;;access,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
        ],
        'language' => [
            'showitem' => 'sys_language_uid, l10n_parent',
        ],
    ],
    'columns' => [
        'event_start' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.event_start',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.event_start.description',
            'config' => [
                'required' => true,
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
        ],
        'event_end' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.event_end',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.event_end.description',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.title',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.title.description',
            'config' => [
                'required' => true,
                'type' => 'input',
            ],
        ],
        'teaser' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.teaser',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.teaser.description',
            'config' => [
                'type' => 'text',
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.description',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.description.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 7,
            ],
        ],
        'location' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.location',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.location.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'map_location' => [
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.map_location',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.map_location.description',
            'config' => [
                'type' => 'user',
                'renderType' => 'rubinEventsMapPicker',
            ],
        ],
        'contacts' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.contacts',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.contacts.description',
            'config' => [
                'type' => 'group',
                'allowed' => 'fe_users',
                'size' => 5,
                'maxitems' => 99,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'creator' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.creator',
            'description' => 'LLL:EXT:rubin_events/Resources/Private/Language/locallang_db.xlf:tx_rubinevents_domain_model_event.creator.description',
            'config' => [
                'type' => 'group',
                'allowed' => 'fe_users',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
    ],
];
