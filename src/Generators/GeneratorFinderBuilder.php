<?php
namespace DBFaker\Generators;


use DBFaker\Generators\Conditions\CheckTypeCondition;
use DBFaker\Generators\Conditions\ConditionInterface;
use Doctrine\DBAL\Types\Type;

class GeneratorFinderBuilder
{

    /**
     * @var array
     */
    private $generators = [];

    /**
     * GeneratorFinderBuilder constructor.
     * @param FakeDataGeneratorInterface[] $generators
     */
    public function __construct(array $generators)
    {
        $this->generators = $generators;
    }


    /**
     * @return GeneratorFinderBuilder
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function buildDefaultFinderBuilder() : GeneratorFinderBuilder
    {
        $builder = new GeneratorFinderBuilder([]);

        $typeFactories = [
            Type::TARRAY => new ComplexObjectGeneratorFactory(),
            Type::SIMPLE_ARRAY => new ComplexObjectGeneratorFactory(0),
            Type::JSON_ARRAY => new ComplexObjectGeneratorFactory(2),
            Type::JSON => new ComplexObjectGeneratorFactory(2),
            Type::OBJECT => new ComplexObjectGeneratorFactory(),

            Type::BOOLEAN => new SimpleGeneratorFactory('boolean'),

            Type::DATETIME => new SimpleGeneratorFactory('dateTime'),
            Type::DATETIMETZ => new SimpleGeneratorFactory('dateTime'),
            Type::DATE => new SimpleGeneratorFactory('dateTime'),
            Type::TIME => new SimpleGeneratorFactory('dateTime'),

            Type::DATETIME_IMMUTABLE => new DateTimeImmutableGeneratorFactory(),
            Type::DATE_IMMUTABLE => new DateTimeImmutableGeneratorFactory(),
            Type::DATETIMETZ_IMMUTABLE => new DateTimeImmutableGeneratorFactory(),
            Type::TIME_IMMUTABLE => new DateTimeImmutableGeneratorFactory(),
            Type::DATEINTERVAL => new DateIntervalGeneratorFactory(),


            Type::BIGINT => new NumericGeneratorFactory(),
            Type::INTEGER => new NumericGeneratorFactory(),
            Type::SMALLINT => new NumericGeneratorFactory(),
            Type::FLOAT => new NumericGeneratorFactory(),
            Type::DECIMAL => new NumericGeneratorFactory(),

            Type::STRING => new TextGeneratorFactory(),
            Type::TEXT => new TextGeneratorFactory(),

            Type::GUID => new SimpleGeneratorFactory('uuid')
        ];

        foreach ($typeFactories as $type => $factory) {
            $builder->addGenerator(
                new CheckTypeCondition(Type::getType($type)),
                $factory
            );
        }

        return $builder;
    }

    public function addGenerator(ConditionInterface $condition, FakeDataGeneratorFactoryInterface $generator) : GeneratorFinderBuilder
    {
        array_unshift($this->generators, [$condition, $generator]);
        return $this;
    }

    public function buildFinder() : GeneratorFinder
    {
        return new GeneratorFinder($this->generators);
    }

}