<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace PageaDev\RubinEvents\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Reads the extension configuration and says which of its values are usable.
 *
 * Lives in its own class because two places need the same answer: the backend module, which lists
 * every setting with a hint, and the module menu indicator, which only needs the overall state.
 *
 * The storage page is looked up with a plain query rather than BackendUtility::getRecord(). The
 * indicator runs while the module menu is being built, and that is too early to rely on TCA being
 * loaded — the lookup would come back empty and report a perfectly fine setting as broken.
 */
final class ConfigurationCheck
{
    public const STATE_OK = 0;
    public const STATE_WARNING = 1;
    public const STATE_ERROR = 2;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * State of every extension setting that can actually be wrong.
     *
     * useSwiper is left out on purpose: a checkbox is either on or off, there is no invalid value
     * to report, and counting it would make the "everything is wrong" case unreachable.
     *
     * Each entry carries "hasValue" next to the value itself. The template cannot ask that
     * question: a colPos of "0" is a perfectly good setting, and Fluid would read it as empty.
     *
     * @return list<array{key: string, value: string, hasValue: bool, valid: bool}>
     */
    public function settings(): array
    {
        $settings = $this->read();

        $storagePid = (string)($settings['storagePid'] ?? '');
        $zoom = (string)($settings['defaultZoom'] ?? '');
        $lat = (string)($settings['defaultLat'] ?? '');
        $lon = (string)($settings['defaultLon'] ?? '');
        $colPos = (string)($settings['defaultColPos'] ?? '');

        return [
            [
                'key' => 'storagePid',
                'value' => $storagePid,
                'hasValue' => $storagePid !== '',
                // Not just "is a number": a PID pointing at a page that does not exist is the
                // failure this module ran into before, so the page has to resolve
                'valid' => $this->storagePid() > 0,
            ],
            [
                'key' => 'defaultZoom',
                'value' => $zoom,
                'hasValue' => $zoom !== '',
                'valid' => ctype_digit($zoom) && (int)$zoom >= 1 && (int)$zoom <= 18,
            ],
            [
                'key' => 'defaultLat',
                'value' => $lat,
                'hasValue' => $lat !== '',
                'valid' => is_numeric($lat) && (float)$lat >= -90 && (float)$lat <= 90,
            ],
            [
                'key' => 'defaultLon',
                'value' => $lon,
                'hasValue' => $lon !== '',
                'valid' => is_numeric($lon) && (float)$lon >= -180 && (float)$lon <= 180,
            ],
            [
                'key' => 'defaultColPos',
                'value' => $colPos,
                'hasValue' => $colPos !== '',
                // An empty value is fine, that is the documented default of 0
                'valid' => $colPos === '' || (ctype_digit($colPos) && (int)$colPos >= 0),
            ],
        ];
    }

    /**
     * Infobox state for the settings indicator: nothing wrong is green, everything wrong is red,
     * anything in between yellow.
     */
    public function state(): int
    {
        $settings = $this->settings();
        $invalid = count(array_filter($settings, static fn(array $setting): bool => !$setting['valid']));

        if ($invalid === 0) {
            return self::STATE_OK;
        }

        return $invalid === count($settings) ? self::STATE_ERROR : self::STATE_WARNING;
    }

    /**
     * Raw storage PID from the extension configuration, without checking whether it resolves.
     */
    public function configuredStoragePid(): int
    {
        return (int)($this->read()['storagePid'] ?? 0);
    }

    /**
     * Folder new records are created in. Returns 0 when the configured page does not exist, so the
     * module never offers a "new record" link that FormEngine would reject afterwards.
     */
    public function storagePid(): int
    {
        $storagePid = $this->configuredStoragePid();

        if ($storagePid <= 0) {
            return 0;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $uid = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $uid === false ? 0 : $storagePid;
    }

    /**
     * Column the page structure importer places its plugins in.
     */
    public function defaultColPos(): int
    {
        $colPos = (int)($this->read()['defaultColPos'] ?? 0);

        return max(0, $colPos);
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        try {
            $settings = $this->extensionConfiguration->get('rubin_events');
        } catch (\Throwable) {
            return [];
        }

        return is_array($settings) ? $settings : [];
    }
}
