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
    protected string $table = 'Users';

    public function up() : void
    {
        $this->getDatabase()->alterTable($this->table)
            ->add(static function (TableDefinition $definition) : void {
                $definition->column('email')->varchar(255)->after('id');
                $definition->column('password')->varchar(255);
            })->run();
    }

    public function down() : void
    {
        $this->getDatabase()->alterTable($this->table)
            ->dropColumn('email', true)
            ->dropColumn('password', true)
            ->run();
    }
};
