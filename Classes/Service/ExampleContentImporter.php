<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace PageaDev\RubinEvents\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Imports the example content shipped in Resources/Private/ExampleContent.
 *
 * The content itself lives in dump.sql so it can be extended without touching PHP. Because a plain
 * dump cannot know the uids it will get, the statements use placeholders that are resolved here —
 * see the header of dump.sql for the list.
 */
final class ExampleContentImporter
{
    public const FOLDER_TITLE = 'Rubin Events – Beispieldaten';

    private const EVENT_TABLE = 'tx_rubinevents_domain_model_event';
    private const FILE_FOLDER = 'rubin_events_examples';
    private const BASE_PATH = 'EXT:rubin_events/Resources/Private/ExampleContent/';
    private const DOKTYPE_FOLDER = 254;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly StorageRepository $storageRepository,
    ) {}

    /**
     * uid of an example folder from an earlier import, 0 when there is none.
     */
    public function findExistingFolder(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $uid = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter(self::FOLDER_TITLE)),
                $queryBuilder->expr()->eq('doktype', $queryBuilder->createNamedParameter(self::DOKTYPE_FOLDER, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)($uid ?: 0);
    }

    /**
     * Creates the storage folder, copies the images into FAL and runs the dump into it.
     *
     * @return array{pageUid: int, contacts: int, events: int, files: int}
     * @throws \RuntimeException when the folder cannot be created or the dump is unreadable
     */
    public function import(): array
    {
        $pageUid = $this->createFolder();
        $files = $this->importImages();
        $statements = $this->readDump();

        // fe_users go first, everything after them may reference their uids
        [$userStatements, $rest] = $this->splitByTable($statements, 'fe_users');

        $connection = $this->connectionPool->getConnectionForTable('fe_users');

        foreach ($userStatements as $statement) {
            $connection->executeStatement($this->replacePlaceholders($statement, $pageUid, $files, []));
        }

        $contacts = $this->importedUsers($pageUid);

        foreach ($rest as $statement) {
            $this->connectionPool
                ->getConnectionForTable($this->tableOf($statement) ?? self::EVENT_TABLE)
                ->executeStatement($this->replacePlaceholders($statement, $pageUid, $files, $contacts));
        }

        $this->updateReferenceIndex($pageUid);

        return [
            'pageUid' => $pageUid,
            'contacts' => count($contacts),
            'events' => $this->countIn(self::EVENT_TABLE, $pageUid),
            'files' => count($files),
        ];
    }

    /**
     * Storage folder for the example records, created at the top level of the page tree so it does
     * not interfere with an existing site structure.
     */
    private function createFolder(): int
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW_rubinevents_examples' => [
                    'pid' => 0,
                    'title' => self::FOLDER_TITLE,
                    'doktype' => self::DOKTYPE_FOLDER,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $pageUid = (int)($dataHandler->substNEWwithIDs['NEW_rubinevents_examples'] ?? 0);

        if ($pageUid <= 0) {
            throw new \RuntimeException(
                'Could not create the storage folder: ' . implode(' ', $dataHandler->errorLog),
                1786240001
            );
        }

        return $pageUid;
    }

    /**
     * Copies the shipped images into the default file storage.
     *
     * @return array<string, int> file name => sys_file uid
     */
    private function importImages(): array
    {
        $storage = $this->storageRepository->getDefaultStorage();

        if ($storage === null) {
            throw new \RuntimeException('No default file storage available for the example images.', 1786240002);
        }

        $folder = $storage->hasFolder(self::FILE_FOLDER)
            ? $storage->getFolder(self::FILE_FOLDER)
            : $storage->createFolder(self::FILE_FOLDER);

        $files = [];

        foreach ($this->imagePaths() as $path) {
            $name = basename($path);

            // removeOriginal = false, otherwise addFile() *moves* the image and empties the
            // extension's own ExampleContent folder
            $file = $folder->hasFile($name)
                ? $folder->getFile($name)
                : $storage->addFile($path, $folder, $name, DuplicationBehavior::REPLACE, false);

            $files[$name] = $file->getUid();
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function imagePaths(): array
    {
        $directory = GeneralUtility::getFileAbsFileName(self::BASE_PATH . 'images/');
        $paths = glob($directory . '*.{webp,jpg,jpeg,png}', GLOB_BRACE) ?: [];

        sort($paths);

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function readDump(): array
    {
        $file = GeneralUtility::getFileAbsFileName(self::BASE_PATH . 'dump.sql');

        if (!is_readable($file)) {
            throw new \RuntimeException('Example dump not readable: ' . self::BASE_PATH . 'dump.sql', 1786240003);
        }

        $sql = preg_replace('/^\s*--.*$/m', '', (string)file_get_contents($file));

        // Statements end with a semicolon at the end of a line. The dump deliberately contains no
        // semicolons inside string literals, so this stays a safe split.
        $statements = preg_split('/;\s*\R/', (string)$sql) ?: [];

        return array_values(array_filter(array_map('trim', $statements), static fn(string $s): bool => $s !== ''));
    }

    /**
     * @param list<string> $statements
     * @return array{0: list<string>, 1: list<string>}
     */
    private function splitByTable(array $statements, string $table): array
    {
        $matching = [];
        $rest = [];

        foreach ($statements as $statement) {
            if ($this->tableOf($statement) === $table) {
                $matching[] = $statement;
            } else {
                $rest[] = $statement;
            }
        }

        return [$matching, $rest];
    }

    private function tableOf(string $statement): ?string
    {
        return preg_match('/^INSERT\s+INTO\s+`?(\w+)`?/i', $statement, $match) === 1 ? $match[1] : null;
    }

    /**
     * @param array<string, int> $files
     * @param array<string, int> $contacts
     */
    private function replacePlaceholders(string $statement, int $pageUid, array $files, array $contacts): string
    {
        $statement = str_replace('###PID###', (string)$pageUid, $statement);

        $statement = preg_replace_callback(
            '/###(FILE|FEUSER):([^#]+)###/',
            static function (array $match) use ($files, $contacts): string {
                $map = $match[1] === 'FILE' ? $files : $contacts;

                if (!isset($map[$match[2]])) {
                    throw new \RuntimeException(
                        sprintf('Example dump references unknown %s "%s".', $match[1], $match[2]),
                        1786240004
                    );
                }

                return (string)$map[$match[2]];
            },
            $statement
        ) ?? $statement;

        // Dates stay relative to the import, so the set always covers past, today and future
        return preg_replace_callback(
            '/###DATE:([^|]+)\|(\d{1,2}):(\d{2})###/',
            static function (array $match): string {
                $day = new \DateTimeImmutable('today');

                return (string)$day->modify($match[1])
                    ->setTime((int)$match[2], (int)$match[3])
                    ->getTimestamp();
            },
            $statement
        ) ?? $statement;
    }

    /**
     * @return array<string, int> username => uid
     */
    private function importedUsers(int $pageUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('uid', 'username')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAllAssociative();

        $users = [];

        foreach ($rows as $row) {
            $users[(string)$row['username']] = (int)$row['uid'];
        }

        return $users;
    }

    private function countIn(string $table, int $pageUid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Raw INSERTs bypass DataHandler, so the reference index has to be brought up to date by hand —
     * otherwise the file references show up as broken in the backend.
     */
    private function updateReferenceIndex(int $pageUid): void
    {
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);

        foreach (['fe_users', 'sys_file_reference', self::EVENT_TABLE] as $table) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();

            $uids = $queryBuilder
                ->select('uid')
                ->from($table)
                ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)))
                ->executeQuery()
                ->fetchFirstColumn();

            foreach ($uids as $uid) {
                $referenceIndex->updateRefIndexTable($table, (int)$uid);
            }
        }
    }
}
