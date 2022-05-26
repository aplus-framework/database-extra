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
    /**
     * @var array<int,string>
     */
    protected array $directories;

    /**
     * @param Database $database
     * @param array<string> $directories
     * @param string $table
     */
    public function __construct(
        Database $database,
        array $directories,
        string $table = 'Migrations'
    ) {
        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }
        $this->setDatabase($database)
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

    public function addDirectory(string $directory) : static
    {
        $realpath = \realpath($directory);
        if ($realpath === false || ! \is_dir($realpath)) {
            throw new InvalidArgumentException('Directory path is invalid: ' . $directory);
        }
        $this->directories[] = $realpath . \DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function getDirectories() : array
    {
        return $this->directories;
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
                $definition->column('migration')->varchar(255);
                $definition->column('timestamp')->timestamp()->notNull();
                $definition->index()->key('migration');
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
        foreach ($this->getDirectories() as $directory) {
            foreach ((array) \glob($directory . '*.php') as $filename) {
                if ($filename && \is_file($filename)) {
                    $files[] = [
                        'basename' => \basename($filename, '.php'),
                        'filename' => $filename,
                    ];
                }
            }
        }
        \usort($files, static function ($file1, $file2) {
            return \strnatcmp($file1['basename'], $file2['basename']);
        });
        $result = [];
        foreach ($files as $file) {
            $result[] = $file['filename'];
        }
        return $result;
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
     * @param int|null $quantity
     *
     * @return Generator<string>
     */
    public function migrateDown(int $quantity = null) : Generator
    {
        $count = 0;
        $last = $this->getLastMigrationName() ?? '';
        foreach ($this->getMigrationsDesc() as $name => $migration) {
            $cmp = \strnatcmp($last, $name);
            if ($cmp < 0) {
                continue;
            }
            $migration->down();
            $this->deleteRow($name);
            yield $name;
            $count++;
            if ($count === $quantity) {
                break;
            }
        }
    }

    /**
     * @param int|null $quantity
     *
     * @return Generator<string>
     */
    public function migrateUp(int $quantity = null) : Generator
    {
        $count = 0;
        $last = $this->getLastMigrationName() ?? '';
        foreach ($this->getMigrationsAsc() as $name => $migration) {
            $cmp = \strnatcmp($last, $name);
            if ($cmp >= 0) {
                continue;
            }
            $migration->up();
            $this->insertRow($name);
            yield $name;
            $count++;
            if ($count === $quantity) {
                break;
            }
        }
    }

    protected function insertRow(string $name) : int | string
    {
        return $this->getDatabase()->insert()
            ->into($this->getTable())
            ->set([
                'migration' => $name,
                'timestamp' => \gmdate('Y-m-d H:i:s'),
            ])->run();
    }

    protected function deleteRow(string $name) : int | string
    {
        return $this->getDatabase()->delete()
            ->from($this->getTable())
            ->whereEqual('migration', $name)
            ->orderByDesc('id')
            ->limit(1)
            ->run();
    }

    /**
     * @param string $name
     *
     * @return Generator<string>
     */
    public function migrateTo(string $name) : Generator
    {
        $last = $this->getLastMigrationName() ?? '';
        $cmp = \strnatcmp($last, $name);
        if ($cmp === 0) {
            return;
        }
        if ($cmp < 0) {
            foreach ($this->getMigrationsAsc() as $n => $migration) {
                if (\strnatcmp($n, $name) > 0) {
                    continue;
                }
                if (\strnatcmp($last, $n) >= 0) {
                    continue;
                }
                $migration->up();
                $this->insertRow($n);
                yield $n;
            }
            return;
        }
        foreach ($this->getMigrationsDesc() as $n => $migration) {
            if (\strnatcmp($name, $n) > 0) {
                continue;
            }
            if (\strnatcmp($last, $n) < 0) {
                continue;
            }
            $migration->down();
            $this->deleteRow($n);
            yield $n;
        }
    }
}
