<?php
namespace DBFaker\Generators;


use DBFaker\Generators\Conditions\ConditionInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class GeneratorFinder
{

    /**
     * @var array
     */
    private $generators;

    /**
     * GeneratorFinder constructor.
     * @param array $generators
     */
    public function __construct(array $generators)
    {
        $this->generators = $generators;
    }

    public function findGenerator(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        foreach ($this->generators as list($condition, $generator)){
            /**  @var $condition ConditionInterface */
            if ($condition->canApply($table, $column)){
                return $generator;
            }
        }
        throw new UnsupportedDataTypeException("Could not find suitable generator for column " . $table->getName() . "." . $column->getName() . " of type : " . $column->getType()->getName());
    }


}