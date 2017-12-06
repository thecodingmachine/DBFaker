<?php
namespace DBFaker\Generators;

use DBFaker\DBFaker;
use Doctrine\DBAL\Schema\Column;
use Faker\Generator;

abstract class UniqueAbleGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var bool
     */
    private $generateUniqueValues;

    /**
     * ComplexObjectGenerator constructor.
     * @param Generator $generator
     * @param int|null $depth
     * @param bool $toArray
     */
    public function __construct($generateUniqueValues = false)
    {
        $this->generateUniqueValues = $generateUniqueValues;
    }

    /**
     * @param Column $column
     * @return mixed
     */
    public function __invoke(Column $column)
    {
        $object = $this->generateRandomValue();
        $iterations = 1;
        while (!$this->isUnique($object)){
            $object = $this->generateRandomValue($column);
            $iterations++;
            if ($iterations > DBFaker::MAX_ITERATIONS_FOR_UNIQUE_VALUE){
                throw new MaxNbOfIterationsForUniqueValueException("Unable to generate a unique value in less then maximumn allowed iterations.");
            }
        }
        $this->storeObjectInGeneratedValues($object);

        return $object;
    }

    protected abstract function generateRandomValue(Column $column);

    private function storeObjectInGeneratedValues($object)
    {
        if ($this->generateUniqueValues){
            $this->generatedValues[] = $object;
        }
    }

    private function isUnique($object)
    {
        if (!$this->generateUniqueValues){
            return true;
        }

        $filtered = array_filter($this->generateUniqueValues, function($value) use ($object) {
            return $object === $value;
        });
        return count($filtered) > 0;
    }


}