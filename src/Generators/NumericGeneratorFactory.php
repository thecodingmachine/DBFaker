<?php
namespace DBFaker\Generators;


use DBFaker\Helpers\NumericColumnLimitHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Faker\Generator;

class NumericGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    public function create(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        $schemaHelper = new SchemaHelper();
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);

        $inspector = new NumericColumnLimitHelper($column);
        $min = $inspector->getMinNumericValue();
        $max = $inspector->getMaxNumericValue();

        return new NumericGenerator($min, $max, $unique);
    }




}