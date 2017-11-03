<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;
use Faker\Generator;

class ComplexObjectGeneratorFactory implements FakeDataGeneratorInterface
{

    /**
     * @param Column $column
     * @return mixed
     */
    public function __invoke(Column $column)
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

    /**
     * @param int $depth
     * @return mixed
     */
    private function generateRandomObject(int $depth) : \stdClass
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

    /**
     * @return mixed
     */
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

    /**
     * @return string
     */
    private function randomPropName() : string
    {
        return str_replace(".", "", $this->generator->userName);
    }


}