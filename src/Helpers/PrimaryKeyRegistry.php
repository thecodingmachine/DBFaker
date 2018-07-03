<?php
namespace DBFaker\Helpers;


use DBFaker\Exceptions\PrimaryKeyColumnMismatchException;
use DBFaker\Exceptions\SchemaLogicException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class PrimaryKeyRegistry
{

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string[]
     */
    private $columnNames;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $values;

    /**
     * @var bool
     */
    private $valuesLoaded = false;

    /**
     * PrimaryKeyRegistry constructor.
     * @param Connection $connection
     * @param Table $table
     * @param SchemaHelper $helper
     * @param bool $isSelfReferencing
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(Connection $connection, Table $table, SchemaHelper $helper, $isSelfReferencing = false)
    {
        $this->connection = $connection;
        $refTable = null;
        $refCols = [];
        foreach ($table->getForeignKeys() as $fk){
            if ($helper->isExtendingKey($fk) && $table->getName() !== $fk->getForeignTableName()){
                $refTable = $fk->getForeignTableName();
                $refCols = $fk->getForeignColumns();
            }
        }
        if ($isSelfReferencing || !$refTable){
            $pk = $table->getPrimaryKey();
            if ($pk === null){
                throw new SchemaLogicException('No PK on table ' . $table->getName());
            }
            $refTable = $table->getName();
            $refCols = $pk->getColumns();
        }
        $this->tableName = $refTable;
        $this->columnNames = $refCols;
        sort($this->columnNames);
    }

    /**
     * Loads all PK values fro ma table and stores them
     * @throws \Doctrine\DBAL\DBALException
     */
    public function loadValuesFromTable() : PrimaryKeyRegistry
    {
        if (!$this->valuesLoaded){
            $this->values = [];
            $colNames = implode(",", $this->columnNames);
            $rows = $this->connection->query("SELECT $colNames FROM " . $this->tableName)->fetchAll();
            foreach ($rows as $row){
                $pk = [];
                foreach ($this->columnNames as $column){
                    $pk[$column] = $row[$column];
                }
                $this->values[] = $pk;
            }
        }
        return $this;
    }

    /**
     * Adds a PK value to the store
     * @param mixed[] $value
     * @throws \DBFaker\Exceptions\PrimaryKeyColumnMismatchException
     */
    public function addValue(array $value) : void
    {
        $keys = array_keys($value);
        sort($keys);
        if ($this->columnNames != $keys){
            throw new PrimaryKeyColumnMismatchException('PrimaryKeys do not match between PKStore and addValue');
        }
        $this->values[] = $value;
    }

    /**
     * @param $excludedValues
     * @return mixed[]
     * @throws \Exception
     */
    public function getRandomValue($excludedValues) : array
    {
        $values = array_filter($this->values, function($value) use ($excludedValues){
            return !\in_array($value, $excludedValues, true);
        });
        return $values[array_rand($values, 1)];
    }

    /**
     * @return mixed[][]
     */
    public function getAllValues() : array
    {
        return $this->values;
    }

}