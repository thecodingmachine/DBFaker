<?php
namespace DBFaker\Generators;

use Faker\Factory;
use Faker\Generator;

class DateTimeImmutableGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var Generator
     */
    private $faker;

    /**
     * ComplexObjectGenerator constructor.
     * @param bool $generateUniqueValues
     */
    public function __construct(bool $generateUniqueValues = false)
    {
        $this->faker = Factory::create();
        if ($generateUniqueValues){
            $this->faker->unique();
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    public function __invoke() : \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->faker->dateTime);
    }

}