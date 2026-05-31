<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

$map = new Map();

// extend backend CSP to allow OSM tile images
$map[Scope::backend()] = new MutationCollection(
    new Mutation(
        MutationMode::Extend,
        Directive::ImgSrc,
        new UriValue('https://*.tile.openstreetmap.org'),
    ),
);

return $map;