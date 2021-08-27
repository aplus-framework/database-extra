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

/**
 * Class Seeder.
 *
 * @package database-extra
 */
abstract class Seeder
{
    protected Database $database;

    /**
     * Seeder constructor.
     *
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Run the Seeder.
     */
    abstract public function run() : void;

    /**
     * Call seeders to run.
     *
     * @param array<int,Seeder|string>|Seeder|string $seeds
     */
    protected function call(array | Seeder | string $seeds) : void
    {
        if (\is_string($seeds)) {
            $seeds = [new $seeds($this->database)];
        } elseif (\is_array($seeds)) {
            foreach ($seeds as &$seed) {
                if (\is_string($seed)) {
                    $seed = new $seed($this->database);
                }
            }
            unset($seed);
        }
        $seeds = \is_array($seeds) ? $seeds : [$seeds];
        foreach ($seeds as $seed) {
            $seed->run();
        }
    }
}
