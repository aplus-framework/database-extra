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

return new class() extends Migration {
    protected string $table = 'Comments';

    public function up() : void
    {
        $this->getDatabase()->createTable($this->table)
            ->definition(static function (TableDefinition $def) : void {
                $def->column('id')->int()->primaryKey()->autoIncrement();
                $def->column('post_id')->int();
                $def->column('user_id')->int();
                $def->column('contents')->text();
                $def->column('createAt')->timestamp();
                $def->index()
                    ->foreignKey('post_id')
                    ->references('Posts', 'id')
                    ->onDelete('CASCADE')
                    ->onUpdate('CASCADE');
                $def->index()
                    ->foreignKey('user_id')
                    ->references('Users', 'id')
                    ->onDelete('CASCADE')
                    ->onUpdate('CASCADE');
            })->run();
    }

    public function down() : void
    {
        $this->getDatabase()->dropTable($this->table)->ifExists()->run();
    }
};
