<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Database Extra Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Database\Extra;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Database\Database;
use Framework\Database\Definition\Table\TableDefinition;
use Generator;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Class Migrator.
 *
 * @package database-extra
 */
class Migrator
{
    /**
     * Migrations Table name.
     */
    protected string $migrationTable = 'Migrations';
    /**
     * Added files.
     *
     * @var array<string,string>
     */
    protected array $files = [];
    protected Database $database;
    protected Locator $locator;

    /**
     * Migrator constructor.
     *
     * @param Database $database
     * @param Locator|null $locator
     */
    public function __construct(Database $database, Locator $locator = null)
    {
        $this->database = $database;
        $this->locator = $locator ?: new Locator(new Autoloader());
        $this->prepare();
    }

    /**
     * Add migrations files.
     *
     * @param string[] $filenames
     *
     * @return static
     */
    public function addFiles(array $filenames) : static
    {
        foreach ($filenames as $filename) {
            $this->files[$this->getFileVersion($filename)] = $filename;
        }
        \ksort($this->files);
        return $this;
    }

    /**
     * Get Migration files.
     *
     * @return array<string,string>
     */
    public function getFiles() : array
    {
        return $this->files;
    }

    /**
     * @param string $table
     *
     * @return static
     */
    public function setMigrationTable(string $table) : static
    {
        $this->migrationTable = $table;
        return $this;
    }

    public function getMigrationTable() : string
    {
        return $this->migrationTable;
    }

    protected function getFileVersion(string $file) : string
    {
        return $this->getFileParts($file)[0];
    }

    /**
     * @param string $file
     *
     * @return array<int,string>
     */
    private function getFileParts(string $file) : array
    {
        $file = \substr($file, \strrpos($file, \DIRECTORY_SEPARATOR) + 1);
        return \explode('-', $file, 2);
    }

    protected function getFileName(string $file) : string
    {
        return \substr($this->getFileParts($file)[1], 0, -4);
    }

    protected function prepare() : void
    {
        $exists = $this->database->query(
            'SHOW TABLES LIKE ' . $this->database->quote($this->getMigrationTable())
        )->fetch();
        if ($exists) {
            return;
        }
        $this->database->createTable()
            ->table($this->getMigrationTable())
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('version')->varchar(32)->primaryKey();
                $definition->column('name')->varchar(255)->notNull();
                $definition->column('migratedAt')->timestamp()->notNull();
            })->run();
    }

    /**
     * Get current migrated version from Database.
     *
     * @return string
     */
    public function getCurrentVersion() : string
    {
        return $this->database->select()
            ->columns('version')
            ->from($this->getMigrationTable())
            ->orderByDesc(static function () : string {
                return 'CAST(`version` AS SIGNED INTEGER)';
            })
            ->orderByAsc('name')
            ->limit(1)
            ->run()
            ->fetch()->version ?? '';
    }

    /**
     * Get Migrations list from Database.
     *
     * @return array<int,object>
     */
    public function getVersions() : array
    {
        return $this->database->select()
            ->from($this->getMigrationTable())
            ->orderByAsc(static function () : string {
                return 'CAST(`version` AS SIGNED INTEGER)';
            })
            ->orderByAsc('name')
            ->run()
            ->fetchAll();
    }

    /**
     * Migrate down all Migration files.
     *
     * @throws ReflectionException If migration class does not exist
     *
     * @return Generator<int,string>
     */
    public function migrateDown() : Generator
    {
        yield from $this->migrateTo('');
    }

    /**
     * Migrate up all Migration files.
     *
     * @throws ReflectionException If migration class does not exist
     *
     * @return Generator<int,string>
     */
    public function migrateUp() : Generator
    {
        yield from $this->migrateTo((string) \array_key_last($this->getFiles()));
    }

    /**
     * Migrate to specific version.
     *
     * @param string $version
     *
     * @throws InvalidArgumentException If migration version is not found
     * @throws ReflectionException If migration class does not exist
     *
     * @return Generator<int,string>
     */
    public function migrateTo(string $version) : Generator
    {
        $currentVersion = $this->getCurrentVersion();
        if ($version === $currentVersion) {
            return;
        }
        if ($version !== '' && ! isset($this->getFiles()[$version])) {
            throw new InvalidArgumentException("Migration version not found: {$version}");
        }
        $direction = 'up';
        if ($version < $currentVersion) {
            $direction = 'down';
            $this->database->delete()
                ->from($this->getMigrationTable())
                ->whereGreaterThan('version', $version)
                ->run();
        }
        $files = $direction === 'up'
            ? $this->getRangeUp($currentVersion, $version)
            : $this->getRangeDown($currentVersion, $version);
        yield from $this->migrate($files, $direction);
    }

    /**
     * @param string $current
     * @param string $target
     *
     * @return array<string,string>
     */
    protected function getRangeDown(string $current, string $target) : array
    {
        $files = [];
        foreach ($this->getFiles() as $version => $file) {
            if ($version <= $current && $version > $target) {
                $files[$version] = $file;
            }
        }
        \krsort($files);
        return $files;
    }

    /**
     * @param string $current
     * @param string $target
     *
     * @return array<string,string>
     */
    protected function getRangeUp(string $current, string $target) : array
    {
        $files = [];
        foreach ($this->getFiles() as $version => $file) {
            if ($version > $current && $version <= $target) {
                $files[$version] = $file;
            }
        }
        return $files;
    }

    /**
     * @param array<string,string> $files
     * @param string $direction
     *
     * @throws ReflectionException If migration class does not exist
     *
     * @return Generator<int,string>
     */
    protected function migrate(array $files, string $direction) : Generator
    {
        foreach ($files as $version => $file) {
            $className = $this->locator->getClassName($file);
            if ($className === null) {
                continue;
            }
            require_once $file;
            $class = new ReflectionClass($className); // @phpstan-ignore-line
            if ( ! $class->isInstantiable() || ! $class->isSubclassOf(Migration::class)) {
                continue;
            }
            (new $className($this->database))->{$direction}();
            if ($direction === 'up') {
                $this->database->insert()
                    ->into($this->getMigrationTable())
                    ->set([
                        'version' => $version,
                        'name' => $this->getFileName($file),
                        'migratedAt' => \gmdate('Y-m-d H:i:s'),
                    ])->run();
            }
            yield $version;
        }
    }
}
