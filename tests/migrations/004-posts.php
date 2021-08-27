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

class PostsMigration extends Migration
{
    public function up() : void
    {
        $this->database->createTable()
            ->table('Posts')
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('id')->int()->primaryKey();
                $definition->column('title')->varchar(255);
            })->run();
    }

    public function down() : void
    {
        $this->database->dropTable()->table('Posts')->ifExists()->run();
    }
}
