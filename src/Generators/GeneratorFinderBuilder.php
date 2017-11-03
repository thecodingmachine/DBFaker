<?php
namespace DBFaker\Generators;


use DBFaker\Generators\Conditions\CheckTypeCondition;
use DBFaker\Generators\Conditions\ConditionInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Faker\Factory;

class GeneratorFinderBuilder
{

    /**
     * @var array
     */
    private $generators = [];

    /**
     * GeneratorFinderBuilder constructor.
     * @param array $generators
     */
    public function __construct(array $generators)
    {
        $this->generators = $generators;
    }


    /**
     * @return GeneratorFinderBuilder
     */
    public static function buildDefaultFinderBuilder(){
        $faker = Factory::create();
        $builder = new GeneratorFinderBuilder([]);

        return $builder
            ->addGenerator(
                new CheckTypeCondition(Type::TARRAY),
                new ComplexObjectGenerator($faker)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::SIMPLE_ARRAY),
                new ComplexObjectGenerator($faker, 0)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::JSON_ARRAY),
                new ComplexObjectGenerator($faker, 1)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::JSON),
                new ComplexObjectGenerator($faker, 1)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::BOOLEAN),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return $faker->boolean;
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DATETIME),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return $faker->dateTime;
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DATETIMETZ),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return $faker->dateTime;
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DATE),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return $faker->dateTime;
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::TIME),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return $faker->dateTime;
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DATETIME_IMMUTABLE),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return \DateTimeImmutable::createFromMutable($faker->dateTime);
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DATE_IMMUTABLE),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return \DateTimeImmutable::createFromMutable($faker->dateTime);
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DATETIMETZ_IMMUTABLE),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return \DateTimeImmutable::createFromMutable($faker->dateTime);
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::TIME_IMMUTABLE),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return \DateTimeImmutable::createFromMutable($faker->dateTime);
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DATEINTERVAL),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    return $faker->dateTime->diff($faker->dateTime);
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::BIGINT),
                new NumericGenerator($faker)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::INTEGER),
                new NumericGenerator($faker)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::SMALLINT),
                new NumericGenerator($faker)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::FLOAT),
                new NumericGenerator($faker)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::DECIMAL),
                new NumericGenerator($faker)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::OBJECT),
                new ComplexObjectGenerator($faker)
            )
            ->addGenerator(
                new CheckTypeCondition(Type::STRING),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    $maxLength = $column->getLength() > 5 ? max($column->getLength(), 300) : $column->getLength();
                    return $column->getLength() > 5 ? $faker->text($maxLength) : substr($faker->text(5), 0, $column->getLength() - 1);
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::TEXT),
                new SimpleGenerator(function(Column $column) use ($faker) {
                    $maxLength = $column->getLength() > 5 ? max($column->getLength(), 300) : $column->getLength();
                    return $column->getLength() > 5 ? $faker->text($maxLength) : substr($faker->text(5), 0, $column->getLength() - 1);
                })
            )
            ->addGenerator(
                new CheckTypeCondition(Type::GUID),
                new SimpleGenerator(function() {
                    $chars = "0123456789abcdef";
                    $groups = [8 ,4, 4, 4, 12];
                    $guid = [];
                    foreach ($groups as $length){
                        $sub = "";
                        for ($i = 0; $i < $length; $i++){
                            $sub .= $chars[random_int(0, count($chars) - 1)];
                        }
                        $guid[] = $sub;
                    }
                    return implode("-", $guid);
                })
            );
    }

    public function addGenerator(ConditionInterface $condition, FakeDataGeneratorInterface $generator) : GeneratorFinderBuilder
    {
        array_unshift($this->generators, [$condition, $generator]);
        return $this;
    }

    public function buildFinder() : GeneratorFinder
    {
        return new GeneratorFinder($this->generators);
    }

}