<?php
/*
 * This file is part of Aplus Framework Database Extra Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\Database\Definition\Table\TableDefinition;
use Framework\Database\Extra\Migration;

class UsersMigration extends Migration
{
    public function up() : void
    {
        $this->database->createTable()
            ->table('Users')
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('id')->int()->primaryKey();
                $definition->column('name')->varchar(32);
            })->run();
    }

    public function down() : void
    {
        $this->database->dropTable()->table('Users')->ifExists()->run();
    }
}
