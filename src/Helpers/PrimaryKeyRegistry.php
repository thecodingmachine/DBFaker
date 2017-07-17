<?php
namespace DBFaker\Helpers;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class PrimaryKeyRegistry
{

    /**
     * @var Table
     */
    private $table;

    /**
     * @var Column[]
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
     * @param Table $table
     * @param $column
     */
    public function __construct(Connection $connection, Table $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->columns = $table->getPrimaryKey()->getColumns();
        sort($this->columns);
    }

    /**
     * Loads all PK values fro ma table and stores them
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
     * @param int[] $value
     * @param string|null $columnName
     * @throws PrimaryKeyColumnMismatchException
     */
    public function addValue(array $value) : void
    {
        $keys = array_keys($value);
        sort($keys);
        if ($this->columns == $keys){
            throw new PrimaryKeyColumnMismatchException("PrimaryKeys do not match between PKStore and addValue");
        }
        $this->values[] = $value;
    }

    /**
     * @return mixed[]
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