<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Database Extra Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Database\Extra;

use Framework\Database\Database;
use Framework\Database\Extra\Migration;

final class MigratorTest extends TestCase
{
    protected MigratorMock $migrator;

    protected function setUp() : void
    {
        $this->migrator = new MigratorMock(
            static::$database,
            __DIR__ . '/migrations'
        );
    }

    protected function tearDown() : void
    {
        static::$database->dropTable()
            ->table($this->migrator->getTable())
            ->ifExists()
            ->run();
        static::$database->dropTable()
            ->table('Comments')
            ->ifExists()
            ->run();
        static::$database->dropTable()
            ->table('Posts')
            ->ifExists()
            ->run();
        static::$database->dropTable()
            ->table('Users')
            ->ifExists()
            ->run();
    }

    public function testDatabase() : void
    {
        self::assertInstanceOf(Database::class, $this->migrator->getDatabase());
        $this->migrator->setDatabase(static::$database);
        self::assertSame(static::$database, $this->migrator->getDatabase());
    }

    public function testDirectory() : void
    {
        self::assertSame(__DIR__ . '/migrations/', $this->migrator->getDirectory());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Directory path is invalid: /foo/bar'
        );
        $this->migrator->setDirectory('/foo/bar');
    }

    public function testTable() : void
    {
        self::assertSame('Migrations', $this->migrator->getTable());
        $this->migrator->setTable('migraciones');
        self::assertSame('migraciones', $this->migrator->getTable());
    }

    public function testLastMigrationName() : void
    {
        self::assertNull($this->migrator->getLastMigrationName());
    }

    public function testGetFiles() : void
    {
        self::assertSame([
            __DIR__ . '/migrations/100_create_table_users.php',
            __DIR__ . '/migrations/300_create_table_posts.php',
            __DIR__ . '/migrations/1000_create_table_comments.php',
            __DIR__ . '/migrations/1100_nothing.php',
            __DIR__ . '/migrations/1200_alter_table_users.php',
        ], $this->migrator->getFiles());
    }

    public function testGetMigrations() : void
    {
        $files = [
            __DIR__ . '/migrations/1.txt',
            __DIR__ . '/migrations/100_create_table_users.php',
            __DIR__ . '/migrations/300_create_table_posts.php',
            __DIR__ . '/migrations/1100_nothing.php',
        ];
        $result = [];
        foreach ($this->migrator->getMigrations($files) as $file => $migration) {
            $result[$file] = $migration;
        }
        self::assertArrayNotHasKey('1', $result);
        self::assertArrayHasKey('100_create_table_users', $result);
        self::assertInstanceOf(Migration::class, $result['100_create_table_users']);
        self::assertArrayHasKey('300_create_table_posts', $result);
        self::assertInstanceOf(Migration::class, $result['300_create_table_posts']);
        self::assertArrayNotHasKey('1100_nothing', $result);
    }

    public function testMigrateUpAndDown() : void
    {
        self::assertNull($this->migrator->getLastMigrationName());
        $up = [];
        foreach ($this->migrator->migrateUp() as $name) {
            $up[] = $name;
        }
        self::assertSame([
            '100_create_table_users',
            '300_create_table_posts',
            '1000_create_table_comments',
            '1200_alter_table_users',
        ], $up);
        self::assertSame('1200_alter_table_users', $this->migrator->getLastMigrationName());
        $up = [];
        foreach ($this->migrator->migrateUp() as $name) {
            $up[] = $name;
        }
        self::assertEmpty($up);
        self::assertSame('1200_alter_table_users', $this->migrator->getLastMigrationName());
        $down = [];
        foreach ($this->migrator->migrateDown() as $name) {
            $down[] = $name;
        }
        self::assertSame([
            '1200_alter_table_users',
            '1000_create_table_comments',
            '300_create_table_posts',
            '100_create_table_users',
        ], $down);
        self::assertNull($this->migrator->getLastMigrationName());
        $down = [];
        foreach ($this->migrator->migrateDown() as $name) {
            $down[] = $name;
        }
        self::assertEmpty($down);
        self::assertNull($this->migrator->getLastMigrationName());
    }

    public function testMigrateTo() : void
    {
        self::assertNull($this->migrator->getLastMigrationName());
        $migrated = [];
        foreach ($this->migrator->migrateTo('') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([], $migrated);
        $migrated = [];
        foreach ($this->migrator->migrateTo('1000') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([
            '100_create_table_users',
            '300_create_table_posts',
        ], $migrated);
        $migrated = [];
        foreach ($this->migrator->migrateTo('1000') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([], $migrated);
        self::assertSame(
            '300_create_table_posts',
            $this->migrator->getLastMigrationName()
        );
        $migrated = [];
        foreach ($this->migrator->migrateTo('1200') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([
            '1000_create_table_comments',
        ], $migrated);
        self::assertSame(
            '1000_create_table_comments',
            $this->migrator->getLastMigrationName()
        );
        $migrated = [];
        foreach ($this->migrator->migrateTo('1201') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([
            '1200_alter_table_users',
        ], $migrated);
        self::assertSame(
            '1200_alter_table_users',
            $this->migrator->getLastMigrationName()
        );
        $migrated = [];
        foreach ($this->migrator->migrateTo('1201') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([], $migrated);
        self::assertSame(
            '1200_alter_table_users',
            $this->migrator->getLastMigrationName()
        );
        $migrated = [];
        foreach ($this->migrator->migrateTo('500') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([
            '1200_alter_table_users',
            '1000_create_table_comments',
        ], $migrated);
        self::assertSame(
            '300_create_table_posts',
            $this->migrator->getLastMigrationName()
        );
        $migrated = [];
        foreach ($this->migrator->migrateTo('1100') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([
            '1000_create_table_comments',
        ], $migrated);
        self::assertSame(
            '1000_create_table_comments',
            $this->migrator->getLastMigrationName()
        );
        $migrated = [];
        foreach ($this->migrator->migrateTo('100') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([
            '1000_create_table_comments',
            '300_create_table_posts',
            '100_create_table_users',
        ], $migrated);
        self::assertNull(
            $this->migrator->getLastMigrationName()
        );
        $migrated = [];
        foreach ($this->migrator->migrateTo('100') as $name) {
            $migrated[] = $name;
        }
        self::assertSame([], $migrated);
    }
}
