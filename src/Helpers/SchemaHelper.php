<?php
namespace DBFaker\Helpers;


use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
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

    public function isPrimaryKeyColumn(Table $table, Column $column)
    {
        return array_search($column->getName(), $table->getPrimaryKeyColumns()) !== false;
    }

    public function getForeignColumn(Table $table, Column $column)
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
                if ($localColumn == $column->getName()){
                    $lookupColumn = $foreignTable->getColumn($foreignColumn);
                }
            }
        }

        if (!$lookupColumn){
            throw new
        }
    }

}