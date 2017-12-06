<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Faker\Factory;
use Faker\Generator;

class DateIntervalGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    public function create(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        $schemaHelper = new SchemaHelper();
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);
        return new DateIntervalGenerator($unique);
    }

}