<?php
namespace DBFaker\Generators;


use DBFaker\Exceptions\UnsupportedDataTypeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

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

    public function __construct($min, $max, $generateUniqueValues = false)
    {
        parent::__construct($generateUniqueValues);
        $this->faker = Factory::create();
        $this->min = $min;
        $this->max = $max;
    }

    protected function generateRandomValue(Column $column){
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

    private function bigRandomNumber($min, $max) {
        $difference   = bcadd(bcsub($max,$min),1);
        $rand_percent = bcdiv(mt_rand(), mt_getrandmax(), 8); // 0 - 1.0
        return bcadd($min, bcmul($difference, $rand_percent, 8), 0);
    }

}