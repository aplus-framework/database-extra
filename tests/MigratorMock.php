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

use Framework\Database\Extra\Migrator;
use Generator;

class MigratorMock extends Migrator
{
    public function getFiles() : array
    {
        return parent::getFiles();
    }

    public function getMigrations(array $files) : Generator
    {
        yield from parent::getMigrations($files);
    }
}
