<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 26/06/18
 * Time: 18:21
 */

namespace DBFaker\Helpers;


use DBFaker\Exceptions\SchemaLogicException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class DBFakerSchemaManager
{
    /**
     * @var mixed[]
     */
    private $foreignKeyMappings;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * DBFakerSchemaManager constructor.
     * @param AbstractSchemaManager $schemaManager
     */
    public function __construct(AbstractSchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;
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

}