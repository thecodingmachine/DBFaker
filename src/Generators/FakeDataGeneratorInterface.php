<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;

interface FakeDataGeneratorInterface
{
    /**
     * @return mixed
     */
    public function getValue(Column $column);

}