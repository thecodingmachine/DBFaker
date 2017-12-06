<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;
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
     * @param Generator $generator
     * @param int|null $depth
     * @param bool $toArray
     */
    public function __construct($generateUniqueValues = false)
    {
        $this->faker = Factory::create();
        if ($generateUniqueValues){
            $this->faker->unique();
        }
    }

    /**
     * @param Column $column
     * @return mixed
     */
    public function __invoke(Column $column)
    {
        return $this->faker->dateTime->diff($this->faker->dateTime);
    }

}