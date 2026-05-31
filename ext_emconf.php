<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Rubin Event Manager',
    'description' => 'A simple Event Management Extension für TYPO3',
    'category' => 'plugin',
    'state' => 'stable',
    'author' => 'Lukas Doerr',
    'author_email' => 'office@pagea.dev',
    'author_company' => 'Pagea Development',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];