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

    /**
     * @var Column
     */
    private $column;

    /**
     * TextGenerator constructor.
     * @param Column $column
     * @param bool $generateUniqueValues
     */
    public function __construct(Column $column, bool $generateUniqueValues = false)
    {
        $this->faker = Factory::create();
        if ($generateUniqueValues){
            $this->faker->unique();
        }
        $this->column = $column;
    }

    /**
     * @return string
     */
    public function __invoke() : string
    {
        $colLength = $this->column->getLength();
        $maxLength = $colLength > 5 ? max($colLength, 300) : $colLength;
        return $colLength > 5 ? $this->faker->text($maxLength) : substr($this->faker->text(5), 0, $colLength - 1);
    }


}