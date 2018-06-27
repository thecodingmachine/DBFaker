<?php
namespace DBFaker\Helpers;


use DBFaker\Exceptions\RuntimeSchemaException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;

class SchemaHelper
{

    /**
     * @param Table $table
     * @param Column $column
     * @return bool
     */
    public function isColumnPartOfUniqueIndex(Table $table, Column $column): bool
    {
        $indexes = $table->getIndexes();
        foreach ($indexes as $index) {
            if (!$index->isUnique()) {
                continue;
            }
            foreach ($index->getColumns() as $columnName) {
                if ($column->getName() === $columnName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isPrimaryKeyColumn(Table $table, Column $column) : bool
    {
        return \in_array($column->getName(), $table->getPrimaryKeyColumns(), true);
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return bool
     */
    public function isColumnPartOfForeignKeyConstraint(Table $table, Column $column): bool
    {
        $constraint = $this->getForeignKeyConstraintByLocal($table, $column);
        return $constraint !== null;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return ForeignKeyConstraint|null
     */
    public function getForeignKeyConstraintByLocal(Table $table, Column $column) : ?ForeignKeyConstraint
    {
        foreach ($table->getForeignKeys() as $foreignKeyConstraint){
            if (\in_array($column->getName(), $foreignKeyConstraint->getLocalColumns(), true)){
                return $foreignKeyConstraint;
            }
        }
        return null;
    }

}