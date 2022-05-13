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

use Framework\Database\Database;
use Framework\Database\Definition\Table\TableDefinition;
use Framework\Helpers\Isolation;
use Generator;
use InvalidArgumentException;

/**
 * Class Migrator.
 *
 * @package database-extra
 */
class Migrator
{
    protected Database $database;
    protected string $table;
    protected string $directory;

    public function __construct(
        Database $database,
        string $directory,
        string $table = 'Migrations'
    ) {
        $this->setDatabase($database)
            ->setDirectory($directory)
            ->setTable($table)
            ->prepare();
    }

    public function setDatabase(Database $database) : static
    {
        $this->database = $database;
        return $this;
    }

    public function getDatabase() : Database
    {
        return $this->database;
    }

    public function setDirectory(string $directory) : static
    {
        $realpath = \realpath($directory);
        if ($realpath === false || ! \is_dir($realpath)) {
            throw new InvalidArgumentException('Directory path is invalid: ' . $directory);
        }
        $this->directory = $realpath . \DIRECTORY_SEPARATOR;
        return $this;
    }

    public function getDirectory() : string
    {
        return $this->directory;
    }

    public function setTable(string $table) : static
    {
        $this->table = $table;
        return $this;
    }

    public function getTable() : string
    {
        return $this->table;
    }

    protected function prepare() : void
    {
        $result = $this->getDatabase()->query(
            'SHOW TABLES LIKE ' . $this->getDatabase()->quote($this->getTable())
        )->fetch();
        if ($result) {
            return;
        }
        $this->getDatabase()->createTable()
            ->table($this->getTable())
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('id')->int()->autoIncrement()->primaryKey();
                $definition->column('migration')->varchar(255)->uniqueKey();
                $definition->column('timestamp')->timestamp()->notNull();
            })->run();
    }

    /**
     * Get current migrated version from Database.
     *
     * @return string|null
     */
    public function getLastMigrationName() : ?string
    {
        return $this->database->select()
            ->from($this->getTable())
            ->orderByDesc('id')
            ->limit(1)
            ->run()
            ->fetch()->migration ?? null;
    }

    /**
     * @return array<int,string>
     */
    protected function getFiles() : array
    {
        $files = [];
        foreach ((array) \glob($this->getDirectory() . '*.php') as $filename) {
            if ($filename && \is_file($filename)) {
                $files[] = $filename;
            }
        }
        \natsort($files);
        return \array_values($files);
    }

    /**
     * @return Generator<string,Migration>
     */
    protected function getMigrationsAsc() : Generator
    {
        yield from $this->getMigrations($this->getFiles());
    }

    /**
     * @return Generator<string,Migration>
     */
    protected function getMigrationsDesc() : Generator
    {
        yield from $this->getMigrations(\array_reverse($this->getFiles()));
    }

    /**
     * @param array<string> $files
     *
     * @return Generator<string,Migration>
     */
    protected function getMigrations(array $files) : Generator
    {
        foreach ($files as $file) {
            $migration = Isolation::require($file);
            if ($migration instanceof Migration) {
                $migration->setDatabase($this->getDatabase());
                $file = \basename($file, '.php');
                yield $file => $migration;
            }
        }
    }

    /**
     * @return Generator<string>
     */
    public function migrateDown() : Generator
    {
        $last = $this->getLastMigrationName();
        foreach ($this->getMigrationsDesc() as $name => $migration) {
            $cmp = \strnatcmp($last ?? '', $name);
            if ($cmp < 0) {
                continue;
            }
            $migration->down();
            $this->getDatabase()->delete()
                ->from($this->getTable())
                ->whereEqual('migration', $name)
                ->run();
            yield $name;
        }
    }

    /**
     * @return Generator<string>
     */
    public function migrateUp() : Generator
    {
        $last = $this->getLastMigrationName();
        foreach ($this->getMigrationsAsc() as $name => $migration) {
            $cmp = \strnatcmp($last ?? '', $name);
            if ($cmp >= 0) {
                continue;
            }
            $migration->up();
            $this->getDatabase()->insert()
                ->into($this->getTable())
                ->set([
                    'migration' => $name,
                    'timestamp' => \gmdate('Y-m-d H:i:s'),
                ])->run();
            yield $name;
        }
    }
}
