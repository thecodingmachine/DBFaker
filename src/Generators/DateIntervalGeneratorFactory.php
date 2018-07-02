<?php
namespace DBFaker\Generators;

use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class DateIntervalGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    /**
     * @param Table $table
     * @param Column $column
     * @param SchemaHelper $schemaHelper
     * @return FakeDataGeneratorInterface
     *
     */
    public function create(Table $table, Column $column, SchemaHelper $schemaHelper) : FakeDataGeneratorInterface
    {
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);
        return new DateIntervalGenerator($unique);
    }

}