<?php
namespace DBFaker\Generators;

use DBFaker\Helpers\NumericColumnLimitHelper;
use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class NumericGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    /**
     * @param Table $table
     * @param Column $column
     * @param SchemaHelper $schemaHelper
     * @return FakeDataGeneratorInterface
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     */
    public function create(Table $table, Column $column, SchemaHelper $schemaHelper) : FakeDataGeneratorInterface
    {
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);

        $inspector = new NumericColumnLimitHelper($column);
        $min = $inspector->getMinNumericValue();
        $max = $inspector->getMaxNumericValue();

        return new NumericGenerator($column, $min, $max, $unique);
    }




}