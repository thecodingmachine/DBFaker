<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 12/06/17
 * Time: 07:21
 */

namespace DBFaker\Generators;


use DBFaker\Helpers\NumericColumnLimitHelper;
use Doctrine\DBAL\Schema\Column;
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
        return $inspector->isIntegerType() ? random_int($min, $max) : $this->faker->randomFloat(10, $min, $max);
    }




}