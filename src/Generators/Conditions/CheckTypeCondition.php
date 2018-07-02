<?php
namespace DBFaker\Generators\Conditions;

use DBFaker\Exceptions\UnsupportedDataTypeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class CheckTypeCondition implements ConditionInterface
{

    /**
     * @var Type
     */
    private $type;

    /**
     * CheckTypeCondition constructor.
     * @param Type $type : the Type to check
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return bool : if the Generator will be applied
     */
    public function canApply(Table $table, Column $column) : bool
    {
        return $this->type === $column->getType();
    }
}