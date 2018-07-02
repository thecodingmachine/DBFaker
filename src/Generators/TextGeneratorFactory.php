<?php
namespace DBFaker\Generators;


use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class TextGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    public function create(Table $table, Column $column, SchemaHelper $schemaHelper) : FakeDataGeneratorInterface
    {
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);
        return new TextGenerator($column, $unique);
    }


}