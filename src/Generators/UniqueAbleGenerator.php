<?php
namespace DBFaker\Generators;

use DBFaker\DBFaker;
use Doctrine\DBAL\Schema\Column;

abstract class UniqueAbleGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var bool
     */
    private $generateUniqueValues;

    /**
     * @var Column
     */
    private $column;

    /**
     * ComplexObjectGenerator constructor.
     * @param Column $column
     * @param bool $generateUniqueValues
     */
    public function __construct(Column $column, $generateUniqueValues = false)
    {
        $this->generateUniqueValues = $generateUniqueValues;
        $this->column = $column;
    }

    /**
     * @param Column $column
     * @return mixed
     */
    public function __invoke()
    {
        $object = $this->generateRandomValue();
        $iterations = 1;
        while (!$this->isUnique($object)){
            $object = $this->generateRandomValue($this->column);
            $iterations++;
            if ($iterations > DBFaker::MAX_ITERATIONS_FOR_UNIQUE_VALUE){
                throw new MaxNbOfIterationsForUniqueValueException("Unable to generate a unique value in less then maximumn allowed iterations.");
            }
        }
        $this->storeObjectInGeneratedValues($object);

        return $object;
    }

    protected abstract function generateRandomValue(Column $column);

    private function storeObjectInGeneratedValues($object) : void
    {
        if ($this->generateUniqueValues){
            $this->generatedValues[] = $object;
        }
    }

    /**
     * @param mixed $object
     * @return bool
     */
    private function isUnique($object) : bool
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