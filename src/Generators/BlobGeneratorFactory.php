<?php
namespace DBFaker\Generators;

use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class BlobGeneratorFactory implements FakeDataGeneratorFactoryInterface
{
    /**
     * @var string
     */
    private $globExpression;

    /**
     * ComplexObjectGenerator constructor.
     * @param string $globExpression
     */
    public function __construct(string $globExpression)
    {
        $this->globExpression= $globExpression;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @param SchemaHelper $helper
     * @return FakeDataGeneratorInterface
     */
    public function create(Table $table, Column $column, SchemaHelper $helper): FakeDataGeneratorInterface
    {
        return new BlobGenerator($this->globExpression);
    }
}