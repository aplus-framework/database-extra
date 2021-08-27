<?php
/*
 * This file is part of Aplus Framework Database Extra Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Database\Extra\Seeds;

use Framework\Database\Extra\Seeder;

class T1 extends Seeder
{
    public function run() : void
    {
        echo __CLASS__ . \PHP_EOL;
        $this->call(T2::class);
        $this->call(new T2($this->database));
        $this->call([
            T2::class,
            new T2($this->database),
        ]);
    }
}
