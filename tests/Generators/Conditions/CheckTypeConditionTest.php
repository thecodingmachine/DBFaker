<?php
namespace DBFaker\Generators\Conditions;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class CheckTypeConditionTest extends TestCase
{
    public function testCanApply(){
        $table = new Table("foo");
        $column = new Column("bar", Type::getType(Type::DECIMAL));

        $checkDecimalTypeCondition = new CheckTypeCondition(Type::getType(Type::DECIMAL));
        $checkTextTypeCondition = new CheckTypeCondition(Type::getType(Type::TEXT));
        $this->assertTrue($checkDecimalTypeCondition->canApply($table, $column));
        $this->assertFalse($checkTextTypeCondition->canApply($table, $column));
    }
}
