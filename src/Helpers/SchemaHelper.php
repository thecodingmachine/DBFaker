<?php
namespace DBFaker\Helpers;

use DBFaker\Exceptions\RuntimeSchemaException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class SchemaHelper
{

    /**
     * @var Schema
     */
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return bool
     */
    public function isColumnPartOfUniqueIndex(Table $table, Column $column): bool
    {
        $indexes = $this->schema->getTable($table->getName())->getIndexes();
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
        $table = $this->schema->getTable($table->getName());
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
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function getForeignKeyConstraintByLocal(Table $table, Column $column) : ?ForeignKeyConstraint
    {
        $table = $this->schema->getTable($table->getName());
        foreach ($table->getForeignKeys() as $foreignKeyConstraint){
            if (\in_array($column->getName(), $foreignKeyConstraint->getLocalColumns(), true)){
                return $foreignKeyConstraint;
            }
        }
        return null;
    }

    /**
     * Tells if $colum in $table is at the same time :
     *  - a primarykey
     *  - a foreign key to another table's primarykey
     * This means current $table is extending the foreign table
     * @param ForeignKeyConstraint $fk
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isExtendingKey(ForeignKeyConstraint $fk) : bool
    {
        if (!$fk->getLocalTable()->hasPrimaryKey()) {
            return false;
        }
        $fkColumnNames = $fk->getLocalColumns();
        $pkColumnNames = $fk->getLocalTable()->getPrimaryKeyColumns();

        sort($fkColumnNames);
        sort($pkColumnNames);

        return $fkColumnNames === $pkColumnNames;
    }

    /**
     * @param ForeignKeyConstraint $fk
     * @return bool
     */
    public function isForeignKetAlsoUniqueIndex($fk) : bool
    {
        $table = $fk->getLocalTable();
        foreach ($table->getIndexes() as $index){
            if ($index->isUnique() && count($index->getColumns()) === count($fk->getLocalColumns())){
                $indexCols = $index->getColumns();
                $fkCols = $fk->getColumns();
                sort($indexCols);
                sort($fkCols);
                if ($indexCols == $fkCols){
                    return true;
                }
            }
        }
        return false;
    }

}