<?php
namespace DBFaker\Generators;


use Doctrine\DBAL\Schema\Column;
use Faker\Factory;
use Faker\Generator;

class TextGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var Generator
     */
    private $faker;

    public function __construct($generateUniqueValues = false)
    {
        $this->faker = Factory::create();
        if ($generateUniqueValues){
            $this->faker->unique();
        }
    }

    public function __invoke(Column $column)
    {
        $maxLength = $column->getLength() > 5 ? max($column->getLength(), 300) : $column->getLength();
        return $column->getLength() > 5 ? $this->faker->text($maxLength) : substr($this->faker->text(5), 0, $column->getLength() - 1);
    }


}