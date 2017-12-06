<?php
namespace DBFaker\Generators;


use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class SimpleGeneratorFactory implements FakeDataGeneratorFactoryInterface
{

    /**
     * @var callable
     */
    private $callback;

    public function __construct(string $fakerCallback)
    {
        $this->callback = $fakerCallback;
    }

    public function create(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        $schemaHelper = new SchemaHelper();
        $unique = $schemaHelper->isColumnPartOfUniqueIndex($table, $column);
        return new SimpleGenerator($this->callback, $unique);
    }


}