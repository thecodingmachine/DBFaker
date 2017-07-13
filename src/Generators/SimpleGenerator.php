<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 12/06/17
 * Time: 07:21
 */

namespace DBFaker\Generators;


use Doctrine\DBAL\Schema\Column;

class SimpleGenerator implements FakeDataGeneratorInterface
{

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function getValue(Column $column)
    {
        return call_user_func($this->callback, $column);
    }


}