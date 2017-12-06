<?php
namespace DBFaker\Generators;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

interface FakeDataGeneratorFactoryInterface
{
    public function create(Table $table, Column $column): FakeDataGeneratorInterface;

}