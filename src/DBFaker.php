<?php
namespace DBFaker;

use DBFaker\Generators\GeneratorFactory;
use DBFaker\Helpers\PrimaryKeyRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Mouf\Utils\Log\Psr\ErrorLogLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DBFaker
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @var GeneratorFactory
     */
    private $generatorFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var array
     */
    private $fakeTableRowNumbers = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var PrimaryKeyRegistry[]
     */
    private $primaryKeyRegistries = [];

    /**
     * @var ForeignKeyConstraint[][]
     */
    private $foreignKeyStore = [];

    /**
     * @var int
     */
    private $nullProbability = 10;

    /*           ___
               /    \
              |      |
              \      /
                    /
                   /    TODO : how do I implement progressbar with internal logger ?
                  |

                  o
    */

    /**
     * DBFaker constructor.
     * @param Connection $connection
     * @param GeneratorFactory $generatorFactory
     * @param SchemaAnalyzer $schemaAnalyzer
     */
    public function __construct(Connection $connection, GeneratorFactory $generatorFactory = null, LoggerInterface $log = null)
    {
        $this->connection = $connection;
        $this->generatorFactory = $generatorFactory ?? new GeneratorFactory();
        $this->log = $log ?? new ErrorLogLogger();
        $this->schemaManager = $connection->getSchemaManager();
    }

    /**
     * Main function : does all the job
     */
    public function fakeDB() : void
    {
        set_time_limit(0);//Import may take a looooong time
        $this->generateFakeData();
        $this->dropForeignKeys();
        $this->insertFakeData();
        $this->restoreForeignKeys();
    }

    /**
     * Generates the fake data for specified tables
     */
    public function generateFakeData() : void
    {
        $this->log->info("Step 1 : Generating data ...");
        foreach ($this->fakeTableRowNumbers as $tableName => $nbLines) {
            $table = $this->schemaManager->listTableDetails($tableName);
            $this->data[$table->getName()] = $this->getFakeDataForTable($table, $nbLines);
        }
    }

    /**
     * @param Table $table the table for which fakse data will be generated
     * @param int $nbLines : the number of lines to generate
     * @return mixed[]
     */
    private function getFakeDataForTable(Table $table, int $nbLines) : array
    {
        $data = [];
        for ($i = 0; $i < $nbLines; $i++) {
            $this->log->info("Step 1 : table " . $table->getName() . "$i / " . $nbLines);
            $row = [];
            foreach ($table->getColumns() as $column) {
                //Check column isn't a PK : PK will be set automatically (NO UUID support)
                if (array_search($column->getName(), $table->getPrimaryKeyColumns()) !== false) {
                    $value = null;
                }
                //Other data will be Faked depending of column's type and attributes
                else {
                    if (!$column->getNotnull() && $this->nullProbabilityOccured()){
                        $value = null;
                    }else{
                        $value = $this->generatorFactory->getGenerator($table, $column)($column);
                    }
                }
                $row[$column->getName()] = $value;
            }
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Drop all foreign keys because it is too complicated to solve the table reference graph in order to generate data in the right order.
     * FKs are stored to be recreated at the end
     */
    private function dropForeignKeys() : void
    {
        $this->foreignKeyStore = [];
        $tables = $this->schemaManager->listTables();
        foreach ($tables as $table){
            foreach ($table->getForeignKeys() as $fk){
                $this->foreignKeyStore[$table->getName()][] = $fk;
                $this->schemaManager->dropForeignKey($fk, $table);
            }
        }
    }

    /**
     * Restore the foreign keys based on the ForeignKeys store built when calling dropForeignKeys()
     */
    private function restoreForeignKeys() : void
    {
        foreach ($this->foreignKeyStore as $tableName => $fks){
            foreach ($fks as $fk){
                $this->schemaManager->createForeignKey($fk, $tableName);
            }
        }
    }

    /**
     * Inserts the data. This is done in 2 steps :
     *   - first insert data for all lines / columns. FKs will be assigned values that only match there type. This step allows to create PK values for second step.
     *   - second turn will update FKs to set random PK values from the previously generated lines.
     */
    private function insertFakeData() : void
    {
        $plateform = $this->connection->getDatabasePlatform();
        //1 - First insert data with no FKs, and null PKs. This will generate primary keys
        foreach ($this->data as $tableName => $rows){
            $table = $this->schemaManager->listTableDetails($tableName);

            //initiate column types for insert
            $types = [];
            $first = reset($rows);
            if ($first){
                foreach ($first as $columnName => $value){
                    /** @var $column Column */
                    $column = $table->getColumn($columnName);
                    $types[] = $column->getType()->getBindingType();
                }
            }

            //insert faked data
            foreach ($rows as $row){
                $dbRow = [];
                foreach ($row as $columnName => $value){
                    $column = $table->getColumn($columnName);
                    $newVal = $column->getType()->convertToDatabaseValue($value, $plateform);
                    $dbRow[$columnName] = $newVal;
                }
                $this->connection->insert($table->getName(), $dbRow, $types);
            }
            //add the new ID to the PKRegistry
            $pkColumnName = $table->getPrimaryKeyColumns()[0];
            $this->getPkRegistry($table)->addValue([$pkColumnName => $this->connection->lastInsertId()]);
        }

        //2 - loop again on table to set FKs now that all PK have been loaded
        foreach ($this->foreignKeyStore as $tableName => $fks){
            if (array_search($tableName, array_keys($this->fakeTableRowNumbers)) === false){
                //only update tables where data has been inserted
                continue;
            }
            $table = $this->schemaManager->listTableDetails($tableName);

            /*
             * Build an array of foreign keys, eg:
             * [
             *      "user_id" => ["table" => "user", "column" => "id"],
             *      "country_id" => ["table" => "country", "column" => "id"]
             * ]
             *
             * foreign tables' PKRegistries will provide final values for local FKs columns
             */
            $fkInfo = [];
            foreach ($fks as $fk){
                $localColums = $fk->getLocalColumns();
                $foreignColumns = $fk->getForeignColumns();
                $foreignTable = $this->schemaManager->listTableDetails($fk->getForeignTableName());
                foreach ($localColums as $index => $localColumn){
                    $foreignColumn = $foreignColumns[$index];
                    $fkInfo[$localColumn] = [
                        "table" => $foreignTable,
                        "column" => $foreignColumn
                    ];
                }
            }

            //Get all the PKs in the table (ie all the lines to update), and update the FKs with random PK values
            $pkValues = $this->getPkRegistry($table)->loadValuesFromTable()->getAllValues();
            foreach ($pkValues as $pkValue){
                $newValues = [];
                foreach ($fkInfo as $localColumn => $foreignData){
                    $foreignTable = $foreignData["table"];
                    $foreignColumn = $foreignData["column"];
                    $fkPkRegistry = $this->getPkRegistry($foreignTable);
                    $randomPk = $fkPkRegistry->loadValuesFromTable()->getRandomValue();
                    $newValues[$localColumn] = $randomPk[$foreignColumn];
                }
                $this->connection->update($tableName, $newValues, $pkValue);
            }
        }
    }

    /**
     * @param Table $table
     * @return PrimaryKeyRegistry
     */
    private function getPkRegistry(Table $table) : PrimaryKeyRegistry
    {
        if (!isset($this->primaryKeyRegistries[$table->getName()])) {
            $this->primaryKeyRegistries[$table->getName()] = new PrimaryKeyRegistry($this->connection, $table);
        }
        return $this->primaryKeyRegistries[$table->getName()];
    }

    /**
     * @return bool : if null value should be generated
     */
    private function nullProbabilityOccured() : bool
    {
        return random_int(0, 100) < $this->nullProbability;
    }

    /**
     * Sets the number of lines that should be generated for each table
     * @param int[] $fakeTableRowNumbers : associative array - Key is the name of the table, and value the number of lines to the faked
     */
    public function setFakeTableRowNumbers(array $fakeTableRowNumbers) : void
    {
        $this->fakeTableRowNumbers = $fakeTableRowNumbers;
    }

    /**
     * Sets the null probability : chance to generate a null value for nullable columns (between 0 and 100, default is 10)
     * @param int $nullProbability
     */
    public function setNullProbability(int $nullProbability) : void
    {
        $this->nullProbability = $nullProbability;
    }

}