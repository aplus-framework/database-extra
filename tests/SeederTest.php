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

use Tests\Database\Extra\Seeds\T1;
use Tests\Database\Extra\Seeds\T2;

final class SeederTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testRunCall() : void
    {
        \ob_start();
        (new T1(static::$database))->run();
        $buffer = \ob_get_clean();
        self::assertSame(
            T1::class . \PHP_EOL
            . T2::class . \PHP_EOL
            . T2::class . \PHP_EOL
            . T2::class . \PHP_EOL
            . T2::class . \PHP_EOL,
            $buffer
        );
    }
}
