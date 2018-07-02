<?php
namespace DBFaker\Generators;

use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Faker\Generator;

class ComplexObjectGeneratorFactory implements FakeDataGeneratorFactoryInterface
{
    /**
     * @var int|null
     */
    private $depth;
    /**
     * @var bool
     */
    private $toArray;

    /**
     * ComplexObjectGenerator constructor.
     * @param int|null $depth
     * @param bool $toArray
     */
    public function __construct(int $depth = null, bool $toArray = true)
    {
        $this->depth = $depth;
        $this->toArray = $toArray;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @param SchemaHelper $schemaHelper
     * @return FakeDataGeneratorInterface
     */
    public function create(Table $table, Column $column, SchemaHelper $schemaHelper): FakeDataGeneratorInterface
    {
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);
        return new ComplexObjectGenerator($column, $this->depth, $this->toArray, $unique);
    }
}