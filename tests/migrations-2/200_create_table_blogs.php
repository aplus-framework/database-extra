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
    protected string $table = 'Blogs';

    public function up() : void
    {
        $this->getDatabase()->createTable($this->table)
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('id')->int()->primaryKey()->autoIncrement();
                $definition->column('name')->varchar(128);
                $definition->column('createdAt')->timestamp();
            })->run();
    }

    public function down() : void
    {
        $this->getDatabase()->dropTable($this->table)->ifExists()->run();
    }
};
