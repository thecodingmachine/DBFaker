<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;
use Faker\Factory;
use Faker\Generator;

class ComplexObjectGenerator extends UniqueAbleGenerator
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
     * @param Column $column
     * @param int|null $depth
     * @param bool $toArray
     * @param bool $generateUniqueValues
     */
    public function __construct(Column $column, int $depth = null, bool $toArray = true, $generateUniqueValues = false)
    {
        parent::__construct($column, $generateUniqueValues);
        $this->generator = Factory::create();
        $this->depth = $depth ?? random_int(2, 5);
        $this->toArray = $toArray;
    }

    protected function generateRandomValue(Column $column)
    {
        return $this->generateRandomObject($this->depth);
    }

    /**
     * @param int $depth
     * @return \stdClass
     */
    private function generateRandomObject(int $depth) : \stdClass
    {
        $obj = new \stdClass();
        $nbProps = random_int(2, 5);
        $hasGoneDeeper = false;
        for ($i = 0; $i < $nbProps; $i++){
            $propName = $this->randomPropName();
            $goDeeper = $depth !== 0 && (random_int(0,10) > 7 || !$hasGoneDeeper);
            if ($goDeeper){
                $hasGoneDeeper = true;
                $value = $this->generateRandomObject($depth - 1);
            }else{
                $value = $this->randomValue();
            }
            $obj->$propName = $value;
        }

        if ($this->toArray){
            $obj = json_decode(json_encode($obj, JSON_OBJECT_AS_ARRAY), true);
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
        return str_replace('.', '', $this->generator->userName);
    }

}