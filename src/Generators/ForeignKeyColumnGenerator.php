<?php

namespace DBFaker\Generators;


use DBFaker\Helpers\DBFakerSchemaManager;
use DBFaker\Helpers\PrimaryKeyRegistry;
use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;

class ForeignKeyColumnGenerator implements FakeDataGeneratorInterface
{
    /**
     * @var Column
     */
    private $foreignColumn;

    /**
     * @var PrimaryKeyRegistry
     */
    private $foreignPkRegistry;

    /**
     * @var bool
     */
    private $generateUniqueValues;

    /**
     * @var mixed[]
     */
    private $alreadyGeneratedValues = [];


    /**
     * ForeignKeyColumnGenerator constructor.
     * @param Table $table
     * @param Column $column
     * @param PrimaryKeyRegistry $foreignPkRegistry
     * @param ForeignKeyConstraint $fk
     * @param DBFakerSchemaManager $schemaManager
     * @param SchemaHelper $schemaHelper
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function __construct(Table $table, Column $column, PrimaryKeyRegistry $foreignPkRegistry,  ForeignKeyConstraint $fk, DBFakerSchemaManager $schemaManager, SchemaHelper $schemaHelper)
    {
        $this->foreignColumn = $schemaManager->getForeignColumn($table, $column);
        $this->foreignPkRegistry = $foreignPkRegistry;
        $this->generateUniqueValues = $schemaHelper->isExtendingKey($fk) && $table->getName() !== $fk->getForeignTableName();
    }

    /**
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __invoke()
    {
        $randomPk = $this->foreignPkRegistry->loadValuesFromTable()->getRandomValue();
        $value = $randomPk[$this->foreignColumn->getName()];
        if ($this->generateUniqueValues){
            while (\in_array($value, $this->alreadyGeneratedValues, true)){
                $randomPk = $this->foreignPkRegistry->loadValuesFromTable()->getRandomValue();
                $value = $randomPk[$this->foreignColumn->getName()];
            }
            $this->alreadyGeneratedValues[] = $value;
        }
        return $value;
    }
}