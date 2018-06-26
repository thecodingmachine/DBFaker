<?php

namespace DBFaker\Generators;


use DBFaker\DBFaker;
use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;

class CompoundColumnGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var mixed[]
     */
    private $possibleValues = [];

    /**
     * @var mixed[]
     */
    private $generatedValues = [];


    /**
     * CompoundColumnGenerator constructor.
     * @param Table $table
     * @param Index $index
     * @param SchemaHelper $schemaHelper
     * @param DBFaker $dbFaker
     * @param AbstractSchemaManager $schemaManager
     * @param int $valuesCount
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function __construct(Table $table, Index $index, SchemaHelper $schemaHelper, DBFaker $dbFaker, AbstractSchemaManager $schemaManager, int $valuesCount)
    {
        foreach ($index->getColumns() as  $columnName){
            //FK or normal column ?
            $column = $table->getColumn($columnName);
            if ($schemaHelper->isColumnPartOfForeignKeyConstraint($table, $column)){
                $fkConstraint = $schemaHelper->getForeignKeyConstraintByLocal($table, $column);
                $foreignTableName = $fkConstraint->getForeignTableName();
                $foreignTable = $schemaManager->listTableDetails($foreignTableName);
                $pkRegistry = $dbFaker->getPkRegistry($foreignTable);
                $this->possibleValues[$columnName] = $pkRegistry->getAllValues();
            }else{
                $generator = $dbFaker->getSimpleColumnGenerator($table, $column);
                for($i = 0; $i < $valuesCount; $i++) {
                    $this->possibleValues[$columnName][] = $generator();
                }
            }
        }
    }
    /**
     * @return mixed[]
     */
    public function __invoke() : array
    {
        $returnVal = [];
        foreach ($this->possibleValues as $columnName => $values){
            $returnVal[$columnName] = $values[array_rand($values)];
        }
        if (!\in_array($returnVal, $this->generatedValues, true)){
            $this->generatedValues[] = $returnVal;
            return $returnVal;
        }
        return $this();
    }

}