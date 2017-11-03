<?php
namespace DBFaker\Generators;


use DBFaker\Helpers\NumericColumnLimitHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Faker\Generator;

class NumericGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var Generator
     */
    private $faker;

    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    public function __invoke(Column $column)
    {
        $inspector = new NumericColumnLimitHelper($column);
        $min = $inspector->getMinNumericValue();
        $max = $inspector->getMaxNumericValue();
        switch ($column->getType()->getName()){
            case Type::BIGINT:
                return $this->bigRandomNumber($min, $max);
            case Type::INTEGER:
            case Type::SMALLINT:
                return random_int($min, $max);
            case Type::DECIMAL:
            case Type::FLOAT:
                return $this->faker->randomFloat(10, $min, $max);
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