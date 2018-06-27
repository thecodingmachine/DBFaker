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
     * @var Table
     */
    private $table;

    /**
     * @var string[]
     */
    private $columns;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $values;

    /**
     * PrimaryKeyRegistry constructor.
     * @param Connection $connection
     * @param Table $table
     * @throws \DBFaker\Exceptions\SchemaLogicException
     */
    public function __construct(Connection $connection, Table $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $pk = $table->getPrimaryKey();
        if ($pk === null){
            throw new SchemaLogicException('No PK on table ' . $table->getName());
        }
        $this->columns = $pk->getColumns();
        sort($this->columns);
    }

    /**
     * Loads all PK values fro ma table and stores them
     * @throws \Doctrine\DBAL\DBALException
     */
    public function loadValuesFromTable() : PrimaryKeyRegistry
    {
        $this->values = [];
        $colNames = implode(",", $this->columns);
        $rows = $this->connection->query("SELECT $colNames FROM " . $this->table->getName())->fetchAll();
        foreach ($rows as $row){
            $pk = [];
            foreach ($this->columns as $column){
                $pk[$column] = $row[$column];
            }
            $this->values[] = $pk;
        }
        return $this;
    }

    /**
     * Adds a PK value to the store
     * @param mixed[] $value
     */
    public function addValue(array $value) : void
    {
        $keys = array_keys($value);
        sort($keys);
        if ($this->columns == $keys){
            throw new PrimaryKeyColumnMismatchException('PrimaryKeys do not match between PKStore and addValue');
        }
        $this->values[] = $value;
    }

    /**
     * @return mixed[]
     * @throws \Exception
     */
    public function getRandomValue() : array
    {
        return $this->values[random_int(0, count($this->values) -1)];
    }

    /**
     * @return mixed[][]
     */
    public function getAllValues() : array
    {
        return $this->values;
    }

}