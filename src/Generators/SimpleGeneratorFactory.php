<?php
namespace DBFaker\Generators;


use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class SimpleGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    /**
     * @var string
     */
    private $callback;

    /**
     * SimpleGeneratorFactory constructor.
     * @param string $fakerCallback
     */
    public function __construct(string $fakerCallback)
    {
        $this->callback = $fakerCallback;
    }

    public function create(Table $table, Column $column, SchemaHelper $schemaHelper) : FakeDataGeneratorInterface
    {
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);
        return new SimpleGenerator($this->callback, $unique);
    }


}