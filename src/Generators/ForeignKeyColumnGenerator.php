<?php

namespace DBFaker\Generators;


use DBFaker\Helpers\PrimaryKeyRegistry;
use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class ForeignKeyColumnGenerator implements FakeDataGeneratorInterface
{

    /**
     * ForeignKeyColumnGenerator constructor.
     * @param Table $table
     * @param Column $column
     * @param PrimaryKeyRegistry $foreignPkRegistry
     * @param SchemaHelper $schemaHelper
     */
    public function __construct(Table $table, Column $column, PrimaryKeyRegistry $foreignPkRegistry, SchemaHelper $schemaHelper)
    {
        $this->foreignColumn = $schemaHelper->getForeignColumn($table, $column);
        $this->foreignPkRegistry = $foreignPkRegistry;
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        $randomPk = $this->foreignPkRegistry->loadValuesFromTable()->getRandomValue();
        return $randomPk[$this->foreignColumn->getName()];
    }
}