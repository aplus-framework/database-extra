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

use Framework\CLI\Streams\Stdout;
use Tests\Database\Extra\Seeds\DatabaseSeeder;
use Tests\Database\Extra\Seeds\PostsSeeder;
use Tests\Database\Extra\Seeds\UsersSeeder;

/**
 * @runTestsInSeparateProcesses
 */
final class SeederTest extends TestCase
{
    public function testCall() : void
    {
        $seeder = new DatabaseSeeder(self::$database);
        Stdout::init();
        $seeder->call(UsersSeeder::class);
        $seeder->call([
            UsersSeeder::class,
            new PostsSeeder(self::$database),
        ]);
        $contents = Stdout::getContents();
        self::assertStringContainsString(UsersSeeder::class, $contents);
        self::assertStringContainsString(PostsSeeder::class, $contents);
    }

    public function testRunCli() : void
    {
        $seeder = new DatabaseSeeder(self::$database);
        Stdout::init();
        $seeder->run();
        $contents = Stdout::getContents();
        self::assertStringContainsString(UsersSeeder::class, $contents);
        self::assertStringContainsString(PostsSeeder::class, $contents);
    }

    public function testRunSilent() : void
    {
        $seeder = new DatabaseSeeder(self::$database);
        Stdout::init();
        $seeder->setSilent();
        $seeder->run();
        $contents = Stdout::getContents();
        self::assertSame('', $contents);
    }
}
