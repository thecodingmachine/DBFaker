<?php
namespace DBFaker\Generators\Conditions;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

interface ConditionInterface
{
    /**
     * @param Table $table
     * @param Column $column
     * @return bool : if the Generator will be applied
     */
    public function canApply(Table $table, Column $column) : bool;

}