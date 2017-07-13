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
    }

    public function loadValuesFromTable()
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

    public function addValue($value, $columnName = null)
    {
        if (count($this->columns) > 1 && $columnName === null){
            throw new \Exception("Trying to add PK value on composite PK without specifying column name in table '". $this->table->getName() ."'");
        }else if ($columnName === null){
            $columnName = $this->columns[0];
        }
        $this->values[] = [$columnName => $value];
    }

    public function getRandomValue(){
        return $this->values[random_int(0, count($this->values) -1)];
    }

    public function getAllValues(){
        return $this->values;
    }

}