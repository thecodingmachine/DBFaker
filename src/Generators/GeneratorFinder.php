<?php
namespace DBFaker\Generators;

use DBFaker\Generators\Conditions\ConditionInterface;
use DBFaker\Exceptions\UnsupportedDataTypeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class GeneratorFinder
{

    /**
     * @var FakeDataGeneratorFactoryInterface[]
     */
    private $generatorFactories;

    /**
     * GeneratorFinder constructor.
     * @param FakeDataGeneratorFactoryInterface[] $generatorFactories
     */
    public function __construct(array $generatorFactories)
    {
        $this->generatorFactories = $generatorFactories;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return FakeDataGeneratorInterface
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     */
    public function findGenerator(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        $generator = null;
        if (!isset($this->generators[$table->getName() . '.' . $column->getName()])){
            foreach ($this->generatorFactories as list($condition, $generatorFactory)){
                /**  @var $condition ConditionInterface */
                if ($condition->canApply($table, $column)){
                    $generator = $generatorFactory->create($table, $column);
                }
            }
            if (!$generator){
                throw new UnsupportedDataTypeException('Could not find suitable generator for column ' . $table->getName() . '.' . $column->getName() . ' of type : ' . $column->getType()->getName());
            }
            $this->generators[$table->getName() . '.' . $column->getName()] = $generator;
        }
        return $this->generators[$table->getName() . '.' . $column->getName()];
    }


}