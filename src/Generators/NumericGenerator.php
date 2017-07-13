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

    public function getValue(Column $column)
    {
        $inspector = new NumericColumnLimitHelper($column);
        return $this->faker->randomFloat(10, $inspector->getMinNumericValue(), $inspector->getMaxNumericValue());
    }




}