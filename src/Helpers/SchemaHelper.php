<?php
namespace DBFaker\Helpers;


use DBFaker\Exceptions\RuntimeSchemaException;
use DBFaker\Exceptions\SchemaLogicException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;

class SchemaHelper
{
    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * SchemaHelper constructor.
     * @param AbstractSchemaManager $schemaManager
     */
    public function __construct(AbstractSchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }


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
     * @return Column
     * @throws \DBFaker\Exceptions\SchemaLogicException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function getForeignColumn(Table $table, Column $column) : Column
    {
        if (isset($this->foreignKeyMappings[$table->getName()][$column->getName()])){
            return $this->foreignKeyMappings[$table->getName()][$column->getName()]["column"];
        }

        $lookupColumn = null;
        $foreignKeys = $table->getForeignKeys();
        foreach ($foreignKeys as $foreignKeyConstraint){
            $localColumns = $foreignKeyConstraint->getLocalColumns();
            $foreignColumns = $foreignKeyConstraint->getForeignColumns();
            $foreignTable = $this->schemaManager->listTableDetails($foreignKeyConstraint->getForeignTableName());
            foreach ($localColumns as $index => $localColumn){
                $foreignColumn = $foreignColumns[$index];
                $this->foreignKeyMappings[$table->getName()][$localColumn] = ["table" => $foreignTable, "column" => $foreignTable->getColumn($foreignColumn)];
                if ($localColumn === $column->getName()){
                    $lookupColumn = $foreignTable->getColumn($foreignColumn);
                }
            }
        }

        if (!$lookupColumn){
            throw new SchemaLogicException("Could not find foreign column for local column '".$table->getName().".".$column->getName()."'");
        }
        return $lookupColumn;
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