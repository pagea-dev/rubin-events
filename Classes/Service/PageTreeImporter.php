<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace PageaDev\RubinEvents\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the example page structure shipped in Resources/Private/ExampleContent/pagetree.*.sql.
 *
 * The structure is a working event setup — list page, slider page, detail page and the storage
 * folder the events live in, with the plugins already pointing at each other. Afterwards the
 * extension configuration is pointed at the new storage folder, which is what makes the example
 * content import land in the right place.
 *
 * Like the example content, the structure lives in SQL rather than in PHP so it can be extended
 * without touching code. See the header of pagetree.en.sql for the placeholders and the two rules
 * that apply when editing those files.
 */
final class PageTreeImporter
{
    private const BASE_PATH = 'EXT:rubin_events/Resources/Private/ExampleContent/';
    private const FALLBACK_LANGUAGE = 'en';
    private const CONTENT_TABLE = 'tt_content';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly ConfigurationCheck $configurationCheck,
        private readonly SiteFinder $siteFinder,
        private readonly CacheManager $cacheManager,
    ) {}

    /**
     * uid of the root page of an earlier import, 0 when there is none.
     */
    public function findExistingRoot(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $uid = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('tx_rubinevents_example', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)($uid ?: 0);
    }

    /**
     * Creates the page structure below the first configured site root and points the extension
     * configuration at the storage folder it created.
     *
     * @param string|null $languageCode backend language of the user, picks the dump variant
     * @return array{root: int, storagePid: int, pages: int, content: int, language: string}
     * @throws \RuntimeException when there is no site to attach to or the dump is unusable
     */
    public function import(?string $languageCode = null): array
    {
        $parentPageUid = $this->findSiteRootPage();
        [$file, $language] = $this->resolveDump($languageCode);
        $statements = $this->parseDump($file);
        $colPos = $this->configurationCheck->defaultColPos();

        // pages and tt_content share a connection in any standard installation. Taking a single one
        // is what makes the transaction below cover the whole structure instead of half of it.
        $connection = $this->connectionPool->getConnectionForTable('pages');

        $pageUids = [];
        $contentUids = [];

        $connection->beginTransaction();

        try {
            foreach ($statements as $statement) {
                $sql = $this->replacePlaceholders($statement['sql'], $parentPageUid, $colPos, $pageUids);
                $connection->executeStatement($sql);
                $uid = (int)$connection->lastInsertId();

                if ($statement['key'] !== null) {
                    $pageUids[$statement['key']] = $uid;
                } elseif ($this->tableOf($statement['sql']) === self::CONTENT_TABLE) {
                    $contentUids[] = $uid;
                }
            }

            $this->requireKeys($pageUids, ['root', 'data']);
            $this->markRoot($connection, $pageUids['root']);
            $this->placeRootLast($connection, $pageUids['root'], $parentPageUid);
            $this->generateSlugs($connection, array_values($pageUids));

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        $this->updateReferenceIndex($pageUids, $contentUids);
        $this->storeStoragePid($pageUids['data']);
        $this->cacheManager->flushCachesInGroup('pages');

        return [
            'root' => $pageUids['root'],
            'storagePid' => $pageUids['data'],
            'pages' => count($pageUids),
            'content' => count($contentUids),
            'language' => $language,
        ];
    }

    /**
     * Page the structure is attached to. Uses the site configuration rather than the is_siteroot
     * flag, because that is what decides whether the new pages end up inside a site at all.
     */
    private function findSiteRootPage(): int
    {
        foreach ($this->siteFinder->getAllSites() as $site) {
            $rootPageId = $site->getRootPageId();

            if ($rootPageId > 0) {
                return $rootPageId;
            }
        }

        throw new \RuntimeException(
            'No site configuration found. The example pages need a site to live in, so create one '
            . 'under Site Management > Sites first.',
            1786240010
        );
    }

    /**
     * Dump file for a backend language, falling back to English.
     *
     * @return array{0: string, 1: string} absolute path and the language it belongs to
     */
    private function resolveDump(?string $languageCode): array
    {
        $candidates = [];

        // Only the language part, and only when it looks like one: the value ends up in a file path
        if ($languageCode !== null && preg_match('/^([a-z]{2})(?:[-_].*)?$/i', $languageCode, $match) === 1) {
            $candidates[] = strtolower($match[1]);
        }

        $candidates[] = self::FALLBACK_LANGUAGE;

        foreach ($candidates as $candidate) {
            $file = GeneralUtility::getFileAbsFileName(self::BASE_PATH . 'pagetree.' . $candidate . '.sql');

            if (is_readable($file)) {
                return [$file, $candidate];
            }
        }

        throw new \RuntimeException(
            'No page structure dump found, expected at least ' . self::BASE_PATH . 'pagetree.'
            . self::FALLBACK_LANGUAGE . '.sql',
            1786240011
        );
    }

    /**
     * Splits the dump into statements and picks up the "-- @as <key>" lines that name a page.
     *
     * @return list<array{key: string|null, sql: string}>
     */
    private function parseDump(string $file): array
    {
        $lines = preg_split('/\R/', (string)file_get_contents($file)) ?: [];

        $statements = [];
        $buffer = '';
        $key = null;

        foreach ($lines as $line) {
            if (preg_match('/^\s*--\s*@as\s+([a-z0-9_]+)\s*$/i', $line, $match) === 1) {
                $key = $match[1];

                continue;
            }

            // Comments only ever occupy a whole line, so this cannot eat part of a value
            if (preg_match('/^\s*--/', $line) === 1) {
                continue;
            }

            if ($buffer === '' && trim($line) === '') {
                continue;
            }

            $buffer .= ($buffer === '' ? '' : "\n") . $line;

            if (preg_match('/;\s*$/', $line) === 1) {
                $statements[] = ['key' => $key, 'sql' => rtrim(trim($buffer), ';')];
                $buffer = '';
                $key = null;
            }
        }

        if (trim($buffer) !== '') {
            throw new \RuntimeException(
                'Page structure dump ends with an unterminated statement: ' . basename($file),
                1786240012
            );
        }

        return $statements;
    }

    /**
     * @param array<string, int> $pageUids
     */
    private function replacePlaceholders(string $sql, int $parentPageUid, int $colPos, array $pageUids): string
    {
        $sql = str_replace(
            ['###PARENT###', '###COLPOS###'],
            [(string)$parentPageUid, (string)$colPos],
            $sql
        );

        return preg_replace_callback(
            '/###PAGE:([a-z0-9_]+)###/i',
            static function (array $match) use ($pageUids): string {
                if (!isset($pageUids[$match[1]])) {
                    throw new \RuntimeException(
                        sprintf('Page structure dump references "%s" before it is created.', $match[1]),
                        1786240013
                    );
                }

                return (string)$pageUids[$match[1]];
            },
            $sql
        ) ?? $sql;
    }

    private function tableOf(string $statement): ?string
    {
        return preg_match('/^INSERT\s+INTO\s+`?(\w+)`?/i', $statement, $match) === 1 ? $match[1] : null;
    }

    /**
     * @param array<string, int> $pageUids
     * @param list<string> $required
     */
    private function requireKeys(array $pageUids, array $required): void
    {
        $missing = array_diff($required, array_keys($pageUids));

        if ($missing !== []) {
            throw new \RuntimeException(
                'Page structure dump is missing the page(s) "' . implode('", "', $missing)
                . '" — every dump has to create them, they are what the importer reports back and '
                . 'what the extension configuration is pointed at.',
                1786240014
            );
        }
    }

    /**
     * The flag identifies an earlier import. Only the root carries it: deleting that page in the
     * backend takes the whole subtree with it, which is the documented way to import again.
     */
    private function markRoot(Connection $connection, int $rootUid): void
    {
        $connection->update('pages', ['tx_rubinevents_example' => 1], ['uid' => $rootUid]);
    }

    /**
     * Puts the structure behind whatever the site already contains instead of into the middle of
     * it. The dump has to name a sorting value, but it cannot know what is already there.
     */
    private function placeRootLast(Connection $connection, int $rootUid, int $parentPageUid): void
    {
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $highest = (int)$queryBuilder
            ->selectLiteral('MAX(' . $queryBuilder->quoteIdentifier('sorting') . ')')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($parentPageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($rootUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();

        $connection->update('pages', ['sorting' => $highest + 256], ['uid' => $rootUid]);
    }

    /**
     * Builds the slugs the raw inserts left empty, parent first, so every page can prefix its
     * parent's slug.
     *
     * @param list<int> $pageUids in creation order
     */
    private function generateSlugs(Connection $connection, array $pageUids): void
    {
        $slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            'pages',
            'slug',
            $GLOBALS['TCA']['pages']['columns']['slug']['config'] ?? []
        );

        foreach ($pageUids as $uid) {
            $page = $connection->select(['pid', 'title'], 'pages', ['uid' => $uid])->fetchAssociative();

            if ($page === false) {
                continue;
            }

            $parentSlug = (string)($connection
                ->select(['slug'], 'pages', ['uid' => (int)$page['pid']])
                ->fetchOne() ?: '');

            $base = rtrim($parentSlug, '/') . '/' . trim($slugHelper->sanitize((string)$page['title']), '/');
            $slug = $base;
            $suffix = 1;

            while ($this->slugExists($connection, $slug, $uid)) {
                $slug = $base . '-' . $suffix++;
            }

            $connection->update('pages', ['slug' => $slug], ['uid' => $uid]);
        }
    }

    private function slugExists(Connection $connection, string $slug, int $ignoreUid): bool
    {
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $uid = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter($slug)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($ignoreUid, Connection::PARAM_INT)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $uid !== false;
    }

    /**
     * Raw INSERTs bypass DataHandler, so the reference index has to be brought up to date by hand.
     * The plugins reference pages through their FlexForm, and those references show up as broken
     * in the backend otherwise.
     *
     * @param array<string, int> $pageUids
     * @param list<int> $contentUids
     */
    private function updateReferenceIndex(array $pageUids, array $contentUids): void
    {
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);

        foreach (array_values($pageUids) as $uid) {
            $referenceIndex->updateRefIndexTable('pages', $uid);
        }

        foreach ($contentUids as $uid) {
            $referenceIndex->updateRefIndexTable(self::CONTENT_TABLE, $uid);
        }
    }

    /**
     * Points the extension configuration at the storage folder that was just created, so new events
     * and the example content import land where the plugins are looking.
     */
    private function storeStoragePid(int $storagePid): void
    {
        try {
            $configuration = $this->extensionConfiguration->get('rubin_events');
        } catch (\Throwable) {
            $configuration = [];
        }

        $configuration = is_array($configuration) ? $configuration : [];
        $configuration['storagePid'] = $storagePid;

        $this->extensionConfiguration->set('rubin_events', $configuration);
    }
}
