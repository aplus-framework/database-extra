<?php
/*
 * This file is part of Aplus Framework Database Extra Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Database\Extra;

use Framework\Database\Extra\Migrator;

final class MigratorTest extends TestCase
{
    protected Migrator $migrator;

    public function setup() : void
    {
        $this->migrator = new Migrator(static::$database);
        $this->migrator->addFiles([
            __DIR__ . '/migrations/001-users.php',
            __DIR__ . '/migrations/2-foo.php',
            __DIR__ . '/migrations/003-bar.php',
            __DIR__ . '/migrations/004-posts.php',
        ]);
    }

    protected function tearDown() : void
    {
        static::$database->dropTable()
            ->table($this->migrator->getMigrationTable())
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

    public function testCurrentVersion() : void
    {
        self::assertSame('', $this->migrator->getCurrentVersion());
    }

    protected function migrateTo(string $version) : void
    {
        foreach ($this->migrator->migrateTo($version) as $item) {
        }
    }

    public function testMigrateTo() : void
    {
        self::assertSame('', $this->migrator->getCurrentVersion());
        $this->migrateTo('001');
        self::assertSame('001', $this->migrator->getCurrentVersion());
        $this->migrateTo('004');
        self::assertSame('004', $this->migrator->getCurrentVersion());
        $this->migrateTo('004');
        self::assertSame('004', $this->migrator->getCurrentVersion());
        $this->migrateTo('001');
        self::assertSame('001', $this->migrator->getCurrentVersion());
        $this->migrateTo('');
        self::assertSame('', $this->migrator->getCurrentVersion());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Migration version not found: 005');
        $this->migrateTo('005');
    }

    public function testMigrateUpAndDown() : void
    {
        self::assertCount(0, $this->migrator->getVersions());
        $versions = [];
        foreach ($this->migrator->migrateUp() as $version) {
            $versions[] = $version;
        }
        self::assertSame(['001', '004'], $versions);
        self::assertSame('004', $this->migrator->getCurrentVersion());
        self::assertCount(2, $this->migrator->getVersions());
        $versions = [];
        foreach ($this->migrator->migrateDown() as $version) {
            $versions[] = $version;
        }
        self::assertSame(['004', '001'], $versions);
        self::assertSame('', $this->migrator->getCurrentVersion());
        self::assertCount(0, $this->migrator->getVersions());
    }

    public function testPrepare() : void
    {
        $migrator = new Migrator(static::$database);
        $migrator->addFiles($this->migrator->getFiles());
        $migrator->setMigrationTable($this->migrator->getMigrationTable());
        self::assertCount(0, $this->migrator->getVersions());
    }
}
