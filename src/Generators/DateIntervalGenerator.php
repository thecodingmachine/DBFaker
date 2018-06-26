<?php
namespace DBFaker\Generators;

use Faker\Factory;
use Faker\Generator;

class DateIntervalGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var Generator
     */
    private $faker;

    /**
     * ComplexObjectGenerator constructor.
     * @param bool $generateUniqueValues
     */
    public function __construct($generateUniqueValues = false)
    {
        $this->faker = Factory::create();
        if ($generateUniqueValues){
            $this->faker->unique();
        }
    }

    /**
     * @return \DateInterval|false
     */
    public function __invoke() : \DateInterval
    {
        return $this->faker->dateTime->diff($this->faker->dateTime);
    }

}