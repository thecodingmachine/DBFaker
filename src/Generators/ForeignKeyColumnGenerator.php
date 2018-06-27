<?php

namespace DBFaker\Generators;


use DBFaker\Helpers\DBFakerSchemaManager;
use DBFaker\Helpers\PrimaryKeyRegistry;
use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
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
     * ForeignKeyColumnGenerator constructor.
     * @param Table $table
     * @param Column $column
     * @param PrimaryKeyRegistry $foreignPkRegistry
     * @param DBFakerSchemaManager $schemaManager
     * @throws \DBFaker\Exceptions\SchemaLogicException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function __construct(Table $table, Column $column, PrimaryKeyRegistry $foreignPkRegistry, DBFakerSchemaManager $schemaManager)
    {
        $this->foreignColumn = $schemaManager->getForeignColumn($table, $column);
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