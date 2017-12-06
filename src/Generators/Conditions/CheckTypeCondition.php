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
     */
    public function __construct(Type $type)
    {
        if (!Type::hasType($type)){
            throw new UnsupportedDataTypeException("Type '$$type' is not supported, please add it to DBAL types list (see Type::addType())");
        }
        $this->type = Type::getType($type);
    }

    /**
     * @return bool : if the Generator will be applied
     */
    public function canApply(Table $table, Column $column) : bool
    {
        return $this->type === $column->getType();
    }
}