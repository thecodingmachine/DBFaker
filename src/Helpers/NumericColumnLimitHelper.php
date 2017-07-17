<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 12/06/17
 * Time: 10:34
 */

namespace DBFaker\Helpers;


use DBFaker\Generators\UnsupportedDataTypeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * Class NumericColumnLimitHelper: gives the min and max numeric values for a column depending
 * on it's type and attributes (precision, scale, unsigned)
 *
 * @package DBFaker\Helpers
 */
class NumericColumnLimitHelper
{

    /**
     * @var Column
     */
    private $column;

    private static $handledNumberTypes = [
        Type::BIGINT,
        Type::DECIMAL,
        Type::INTEGER,
        Type::SMALLINT,
        Type::FLOAT
    ];

    /**
     * NumericColumnLimitHelper constructor
     * @param Column $column
     */
    public function __construct(Column $column)
    {
        if (array_search($column->getType()->getName(), self::$handledNumberTypes) === false){
            throw new UnsupportedDataTypeException("Unsupported column type : " .
                $column->getType()->getName() . "only " .
                implode("', '", self::$handledNumberTypes) ." types are supported."
            );
        }
        $this->column = $column;
    }

    /**
     * returns the min numeric value for the column
     * @return mixed
     */
    public function getMinNumericValue()
    {
        $precisionValue = $this->getAbsValueByLengthPrecision($this->column);
        switch ($this->column->getType()->getName()){
            case Type::BIGINT:
                return $this->column->getUnsigned() ? 0 : bcpow(2, 63);
                break;
            case Type::INTEGER:
                return $this->column->getUnsigned() ? 0 : max(-$precisionValue, -bcpow(2, 31));
                break;
            case Type::SMALLINT:
                return $this->column->getUnsigned() ? 0 : -bcpow(2, 15);
                break;
            case Type::DECIMAL:
                return $this->column->getUnsigned() ? 0 : -$precisionValue;
                break;
            case Type::FLOAT:
                return $this->column->getUnsigned() ? 0 : -1.79 * bcpow(10, 308);
                break;
        }
    }

    /**
     * returns the max numeric value for the column
     * @return mixed
     */
    public function getMaxNumericValue()
    {
        $precisionValue = $this->getAbsValueByLengthPrecision($this->column);
        switch ($this->column->getType()->getName()){
            case Type::BIGINT:
                return $this->column->getUnsigned() ? bcpow(2, 64) : bcpow(2, 63) - 1;
                break;
            case Type::INTEGER:
                return $this->column->getUnsigned() ? bcpow(2, 32) : min($precisionValue, bcpow(2, 31) - 1);
                break;
            case Type::SMALLINT:
                return $this->column->getUnsigned() ? bcpow(2, 16) : bcpow(2, 15) - 1;
                break;
            case Type::DECIMAL:
                return $this->column->getUnsigned() ? 0 : $precisionValue;
                break;
            case Type::FLOAT:
                return 1.79 * bcpow(10, 308);
                break;
        }
    }

    /**
     * @param Column $column
     * @return mixed
     */
    private function getAbsValueByLengthPrecision(Column $column)
    {
        switch ($column->getType()->getName()){
            case Type::DECIMAL:
                $str = str_repeat(9, $column->getScale());
                return (double) substr_replace($str, ".", $column->getScale() - $column->getPrecision(), 0);
                break;
            case Type::INTEGER:
                $str = str_repeat(9, $column->getPrecision() - 1);
                return (int) $str;
                break;
        }
    }

    /**
     * @return bool : tells if the type has no decimal support
     */
    public function isIntegerType() : bool
    {
        return in_array($this->column->getType()->getName(), [Type::DECIMAL, Type::FLOAT]) === false;

    }

}