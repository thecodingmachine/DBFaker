<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 09/06/17
 * Time: 23:25
 */

namespace DBFaker\Generators;


use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Faker\Factory;
use Faker\Generator;

class GeneratorFactory
{
    /**
     * @var array
     */
    private $columnGenerators = [];

    /**
     * @var array
     */
    private $defaultGenerators = [];

    /**
     * @var array
     */
    private $dynamicGenerators = [];

    /**
     * @var Generator
     */
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    /*           ___
               /    \
              |      |
              \      / TODO Can I pass $faker to SimpleGenerator's _constructor and avoid paasing it through 'use',
                    /  TODO Maybe Generators should all be instanciated with column & faker instance
                  /    TODO I dont think this is a real factory... should it be ? Should I rename ?
                  |

                  o
    */

    /**
     * @param Table $table
     * @param Column $column
     * @param Generator $faker
     * @return FakeDataGeneratorInterface
     * @throws \Exception
     */
    public function getGenerator(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        $generator = null;
        $identifier = $table->getName() . "." . $column->getName();
        foreach ($this->dynamicGenerators as $dynamicGenerator){
            if ($dynamicGenerator["conditionCallback"]($column)){
                $generator = $dynamicGenerator["generator"];
            }
        }
        if (!$generator){
            if (isset($this->columnGenerators[$identifier])) {
                $generator = $this->columnGenerators[$identifier];
            }else{
                $generator = $this->getDefaultGenerator($column->getType());
            }
        }
        if ($generator === null){
            throw new \RuntimeException("No colum, type nor default generator found for column '".$identifier."' of type '".$column->getType()->getName()."', you must provide it !");
        }
        return $generator;
    }

    /**
     * @param Column $column
     * @return FakeDataGeneratorInterface
     */
    private function getDefaultGenerator(Type $type) : FakeDataGeneratorInterface
    {
        $faker = $this->faker;
        $type = $type->getName();
        if (!isset($this->defaultGenerators[$type])){
            switch ($type){
                case Type::TARRAY :
                    $generator = new ComplexObjectGenerator($faker);
                    break;
                case Type::SIMPLE_ARRAY :
                    $generator = new ComplexObjectGenerator($faker, 0);
                    break;
                case Type::JSON_ARRAY :
                    $generator = new ComplexObjectGenerator($faker, 1);
                    break;
                case Type::BOOLEAN :
                    $generator = new SimpleGenerator(function(Column $column) use ($faker) {
                        return $faker->boolean;
                    });
                    break;
                case Type::DATETIME :
                case Type::DATETIMETZ :
                case Type::DATE :
                case Type::TIME :
                    $generator = new SimpleGenerator(function(Column $column) use ($faker) {
                        return $faker->dateTime;
                    });
                    break;
                case Type::BIGINT :
                case Type::INTEGER :
                case Type::SMALLINT :
                    $generator = new NumericGenerator($faker);
                    break;
                case Type::FLOAT :
                    $generator = new NumericGenerator($faker);
                    break;
                case Type::OBJECT :
                    $generator = new ComplexObjectGenerator($faker);
                    break;
                case Type::GUID :
                    $generator = new SimpleGenerator(function() {
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
                    });
                    break;
                case Type::STRING :
                case Type::TEXT :
                    $generator = new SimpleGenerator(function(Column $column) use ($faker) {
                        $maxLength = $column->getLength() > 5 ? max($column->getLength(), 300) : $column->getLength();
                        return $column->getLength() > 5 ? $faker->text($maxLength) : substr($faker->text(5), 0, $column->getLength() - 1);
                    });
                    break;
                case Type::BINARY :
                case Type::BLOB :
                    $generator = null;
                    break;
                default :
                    throw new UnsupportedDataTypeException("Data type : '" . $type . "' is not supported");
            }
            $this->defaultGenerators[$type] = $generator;
        }
        return $this->defaultGenerators[$type];
    }

    /**
     * @param string $table the name of the table that contains the column you are looking for
     * @param string $column the name of the column which's default generator should be overriden
     * @param  callable $generator : "real" callback with Column in argument or FakeDataGeneratorInterface
     */
    public function setGeneratorForColumn(string $table, string $column, callable $generator) : void
    {
        if (!$generator instanceof FakeDataGeneratorInterface){
            $generator = new SimpleGenerator($generator);
        }
        $this->columnGenerators[$table . "." . $column] = $generator;
    }

    /**
     * @param  callable $conditionCallback : callback that returns a boolean (true if generator override should happend) takes the Column as input
     * @param  callable $generator : "real" callback with Column in argument or FakeDataGeneratorInterface
     */
    public function setGenerator(callable $conditionCallback, callable $generator) : void
    {
        if (!$generator instanceof FakeDataGeneratorInterface){
            $generator = new SimpleGenerator($generator);
        }
        $this->dynamicGenerators[] = [
            "conditionCallback" => $conditionCallback,
            "generator" => $generator
        ];
    }

    /**
     * @param array $generators
     */
    public function setDefaultGenerator(Type $type, FakeDataGeneratorInterface $generator) : void
    {
        $this->defaultGenerators[$type] = $generator;
    }

}