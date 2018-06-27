<?php
namespace DBFaker\Generators;


use DBFaker\Exceptions\UnsupportedDataTypeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Faker\Factory;
use Faker\Generator;

class NumericGenerator extends UniqueAbleGenerator
{

    /**
     * @var mixed
     */
    private $min;
    /**
     * @var mixed
     */
    private $max;
    /**
     * @var Column
     */
    private $column;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * NumericGenerator constructor.
     * @param Column $column
     * @param mixed $min
     * @param mixed $max
     * @param bool $generateUniqueValues
     */
    public function __construct(Column $column, $min, $max, $generateUniqueValues = false)
    {
        parent::__construct($column, $generateUniqueValues);
        $this->faker = Factory::create();
        $this->min = $min;
        $this->max = $max;
        $this->column = $column;
    }

    /**
     * @param Column $column
     * @return int|string|float
     * @throws \Exception
     */
    protected function generateRandomValue(Column $column)
    {
        switch ($column->getType()->getName()){
            case Type::BIGINT:
                return $this->bigRandomNumber();
            case Type::INTEGER:
            case Type::SMALLINT:
                return random_int($this->min, $this->max);
            case Type::DECIMAL:
            case Type::FLOAT:
                return $this->faker->randomFloat(10, $this->min, $this->max);
            default:
                throw new UnsupportedDataTypeException("Cannot generate numeric value for Type : '".$column->getType()->getName()."'");
        }
    }

    /**
     * @return string
     */
    private function bigRandomNumber() : string
    {
        $difference   = bcadd(bcsub($this->max,$this->min),"1");
        $rand_percent = bcdiv((string) mt_rand(), (string) mt_getrandmax(), 8); // 0 - 1.0
        return bcadd($this->min, bcmul($difference, $rand_percent, 8), 0);
    }

}