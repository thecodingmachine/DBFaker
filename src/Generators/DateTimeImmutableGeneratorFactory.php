<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Faker\Factory;
use Faker\Generator;

class DateTimeImmutableGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    /**
     * @param Table $table
     * @param Column $column
     * @return FakeDataGeneratorInterface
     */
    public function create(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        $schemaHelper = new SchemaHelper();
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);
        return new DateTimeImmutableGenerator($unique);
    }

}