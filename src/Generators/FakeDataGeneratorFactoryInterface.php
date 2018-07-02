<?php
namespace DBFaker\Generators;

use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

interface FakeDataGeneratorFactoryInterface
{
    public function create(Table $table, Column $column, SchemaHelper $helper): FakeDataGeneratorInterface;

}