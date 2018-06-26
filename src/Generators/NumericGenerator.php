<?php
namespace DBFaker\Generators;


use DBFaker\Exceptions\UnsupportedDataTypeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Faker\Generator;

class NumericGenerator extends UniqueAbleGenerator
{

    /**
     * @var
     */
    private $min;
    /**
     * @var
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
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     */
    protected function generateRandomValue(Column $column)
    {
        switch ($column->getType()->getName()){
            case Type::BIGINT:
                return $this->bigRandomNumber($this->min, $this->max);
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
     * @param $min
     * @param $max
     * @return string
     */
    private function bigRandomNumber($min, $max) : string
    {
        $difference   = bcadd(bcsub($max,$min),1);
        $rand_percent = bcdiv(mt_rand(), mt_getrandmax(), 8); // 0 - 1.0
        return bcadd($min, bcmul($difference, $rand_percent, 8), 0);
    }

}