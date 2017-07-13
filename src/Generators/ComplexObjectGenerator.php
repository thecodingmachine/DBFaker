<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;
use Faker\Generator;

class ComplexObjectGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var boolean
     */
    private $toArray;

    /**
     * ComplexObjectGenerator constructor.
     * @param Generator $generator
     */
    public function __construct(Generator $generator, $depth = null, $toArray = true)
    {
        $this->generator = $generator;
        $this->depth = $depth;
        $this->toArray = $toArray;
    }


    public function getValue(Column $column)
    {
        if ($this->depth === null){
            $this->depth = random_int(2, 5);
        }
        $object = $this->generateRandomObject($this->depth);
        if ($this->toArray){
            $object = json_decode(json_encode($object, JSON_OBJECT_AS_ARRAY), true);
        }
        return $object;
    }

    private function generateRandomObject($depth)
    {
        $obj = new \stdClass();
        $nbProps = random_int(2, 5);
        $hasGoneDeeper = false;
        for ($i = 0; $i < $nbProps; $i++){
            $propName = $this->randomPropName();
            $goDeeper = $depth != 0 && (random_int(0,10) > 7 || !$hasGoneDeeper);
            if ($goDeeper){
                $hasGoneDeeper = true;
                $value = $this->generateRandomObject($depth - 1);
            }else{
                $value = $this->randomValue();
            }
            $obj->$propName = $value;
        }
        return $obj;
    }

    private function randomValue()
    {
        $generators = [
            $this->generator->biasedNumberBetween(),
            $this->generator->boolean,
            $this->generator->century,
            $this->generator->city,
            $this->generator->creditCardExpirationDate,
            $this->generator->dateTime,
            $this->generator->longitude
        ];
        return $generators[array_rand($generators)];
    }

    private function randomPropName()
    {
        return $this->generator->userName;
    }


}