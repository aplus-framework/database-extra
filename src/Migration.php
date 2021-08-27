<?php
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
 * Class Migration.
 *
 * @package database-extra
 */
abstract class Migration
{
    protected Database $database;

    /**
     * Migration constructor.
     *
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Run migration up.
     */
    abstract public function up() : void;

    /**
     * Run migration down.
     */
    abstract public function down() : void;
}
