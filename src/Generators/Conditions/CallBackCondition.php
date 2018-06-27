<?php
namespace DBFaker\Generators\Conditions;


use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class CallBackCondition implements ConditionInterface
{

    /**
     * @var callable
     */
    private $callback;

    /**
     * CheckTypeCondition constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return bool : if the Generator will be applied
     */
    public function canApply(Table $table, Column $column) : bool
    {
        return \call_user_func($this->callback, $table, $column);
    }
}