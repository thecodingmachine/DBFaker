<?php
namespace DBFaker\Generators;


use DBFaker\Generators\Conditions\ConditionInterface;
use DBFaker\Exceptions\UnsupportedDataTypeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class GeneratorFinder
{

    /**
     * @var array
     */
    private $generatorFactories;

    /**
     * GeneratorFinder constructor.
     * @param array $generators
     */
    public function __construct(array $generatorFactories)
    {
        $this->generatorFactories = $generatorFactories;
    }

    public function findGenerator(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        $generator = null;
        if (!isset($this->generators[$table->getName() . "." . $column->getName()])){
            foreach ($this->generatorFactories as list($condition, $generatorFactory)){
                /**  @var $condition ConditionInterface */
                if ($condition->canApply($table, $column)){
                    $generator = $generatorFactory->create($table, $column);
                }
            }
            if (!$generator){
                throw new UnsupportedDataTypeException("Could not find suitable generator for column " . $table->getName() . "." . $column->getName() . " of type : " . $column->getType()->getName());
            }
            $this->generators[$table->getName() . "." . $column->getName()] = $generator;
        }
        return $this->generators[$table->getName() . "." . $column->getName()];
    }


}