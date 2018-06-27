<?php
namespace DBFaker;

use DBFaker\Generators\CompoundColumnGenerator;
use DBFaker\Generators\FakeDataGeneratorInterface;
use DBFaker\Generators\ForeignKeyColumnGenerator;
use DBFaker\Generators\GeneratorFactory;
use DBFaker\Generators\GeneratorFinder;
use DBFaker\Helpers\DBFakerSchemaManager;
use DBFaker\Helpers\PrimaryKeyRegistry;
use DBFaker\Helpers\SchemaHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Mouf\Utils\Log\Psr\ErrorLogLogger;
use Psr\Log\LoggerInterface;

class DBFaker
{
    public const MAX_ITERATIONS_FOR_UNIQUE_VALUE = 1000;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @var GeneratorFinder
     */
    private $generatorFinder;

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
     * @var Index[][]
     */
    private $multipleUniqueContraintStore = [];

    /**
     * @var int
     */
    private $nullProbability = 10;

    /**
     * @var SchemaHelper
     */
    private $schemaHelper;

    /**
     * @var CompoundColumnGenerator[]
     */
    private $compoundColumnGenerators;

    /**
     * @var ForeignKeyColumnGenerator[]
     */
    private $fkColumnsGenerators;

    /*           __
               /    \
              |      |
              \      /
                    /
                   /    TODO : how do I implement progressbar with internal logger ?
                  |     TODO: Handle DB extend
                        TODO: Handle unique indexes
                  o
    */

    /**
     * DBFaker constructor.
     * @param Connection $connection
     * @param GeneratorFinder $generatorFinder
     * @param LoggerInterface $log
     * @internal param SchemaAnalyzer $schemaAnalyzer
     */
    public function __construct(Connection $connection, GeneratorFinder $generatorFinder, LoggerInterface $log = null)
    {
        $this->connection = $connection;
        $this->generatorFinder = $generatorFinder;
        $this->log = $log ?? new ErrorLogLogger();
        $this->schemaManager = $connection->getSchemaManager();
        $this->schemaHelper = new SchemaHelper();
    }

    /**
     * Main function : does all the job
     */
    public function fakeDB() : void
    {
        set_time_limit(0);//Import may take a looooooong time :)
        $this->generateFakeData();
        $this->dropForeignKeys();
        $this->dropMultipleUniqueContraints();
        $this->insertFakeData();
        $this->restoreForeignKeys();
        $this->restoreMultipleUniqueContraints();
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
     * @param Table $table the table for which fake data will be generated
     * @param int $nbLines : the number of lines to generate
     * @return mixed[]
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     * @throws \DBFaker\Exceptions\PrimaryKeyColumnMismatchException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getFakeDataForTable(Table $table, int $nbLines) : array
    {
        $data = [];
        for ($i = 0; $i < $nbLines; $i++) {
            $this->log->info('Step 1 : table ' . $table->getName() . "$i / " . $nbLines);
            $row = [];
            foreach ($table->getColumns() as $column) {
                //IF column is a PK and Autoincrement then values will be set to null, let the database generate them
                if ($this->schemaHelper->isPrimaryKeyColumn($table, $column) && $column->getAutoincrement()) {
                    $value = null;
                }
                //Other data will be Faked depending of column's type and attributes. FKs to, but their values wil be overridden.
                else {
                    if (!$column->getNotnull() && $this->nullProbabilityOccured()){
                        $value = null;
                    }else{
                        $generator = $this->getSimpleColumnGenerator($table, $column);
                        $value = $generator();
                    }
                }
                $row[$column->getName()] = $value;
            }
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Inserts the data. This is done in 2 steps :
     *   - first insert data for all lines / columns. FKs will be assigned values that only match there type. This step allows to create PK values for second step.
     *   - second turn will update FKs to set random PK values from the previously generated lines.
     */
    private function insertFakeData() : void
    {
        //1 - First insert data with no FKs, and null PKs. This will generate primary keys
        $this->insertWithoutFksAndUniqueIndexes();

        //2 - loop on multiple unique index contraints (that may include FKs)
        $handledColumns = $this->updateMulipleUniqueIndexedColumns();

        //3 - loop again to set FKs now that all PK have been loaded
        $this->updateRemainingForeignKeys($handledColumns);
    }

    /**
     * Inserts base data :
     *    - AutoIncrement PKs will be generated and stored
     *    - ForeignKey and Multiple Unique Indexes are ignored, because we need self-generated PK values
     */
    private function insertWithoutFksAndUniqueIndexes(): void
    {
        $plateform = $this->connection->getDatabasePlatform();
        foreach ($this->data as $tableName => $rows){
            $table = $this->schemaManager->listTableDetails($tableName);

            //initiate column types for insert
            $types = [];
            $first = reset($rows);
            if ($first){
                foreach ($first as $columnName => $value){
                    /** @var Column $column */
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
            //if autoincrement, add the new ID to the PKRegistry
            $pkColumnName = $table->getPrimaryKeyColumns()[0];
            $pkColumn = $table->getColumn($pkColumnName);
            if ($this->schemaHelper->isPrimaryKeyColumn($table, $pkColumn)){
                $this->getPkRegistry($table)->addValue([$pkColumnName => $this->connection->lastInsertId()]);
            }
        }
    }

    /**
     * @param Table $table
     * @return PrimaryKeyRegistry
     */
    public function getPkRegistry(Table $table) : PrimaryKeyRegistry
    {
        if (!isset($this->primaryKeyRegistries[$table->getName()])) {
            $this->primaryKeyRegistries[$table->getName()] = new PrimaryKeyRegistry($this->connection, $table);
        }
        return $this->primaryKeyRegistries[$table->getName()];
    }

    /**
     * @return bool : if null value should be generated
     * @throws \Exception
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

    /**
     * Drop all foreign keys because it is too complicated to solve the table reference graph in order to generate data in the right order.
     * FKs are stored to be recreated at the end
     */
    private function dropForeignKeys() : void
    {
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

    private function dropMultipleUniqueContraints(): void
    {
        $tables = $this->schemaManager->listTables();
        foreach ($tables as $table){
            foreach ($table->getIndexes() as $index){
                if ($index->isUnique() && count($index->getColumns()) > 1)
                $this->multipleUniqueContraintStore[$table->getName()][] = $index;
                $this->schemaManager->dropIndex($index->getName(), $table->getName());
            }
        }
    }

    private function restoreMultipleUniqueContraints(): void
    {
        foreach ($this->multipleUniqueContraintStore as $tableName => $indexes){
            foreach ($indexes as $index){
                $this->schemaManager->createIndex($index, $tableName);
            }
        }
    }

    /**
     *
     * Sets data for a group of columns (2 or more) that are bound by a unique constraint
     * @return string[]
     */
    private function updateMulipleUniqueIndexedColumns() : array
    {
        $handledColumns = [];

        foreach ($this->multipleUniqueContraintStore as $tableName => $indexes){
            $table = $this->schemaManager->listTableDetails($tableName);

            foreach ($indexes as $index){
                foreach ($index->getColumns() as $columnName){
                    $fullColumnName = $tableName.".".$columnName;
                    if (!\in_array($fullColumnName, $handledColumns)){
                        $handledColumns[] = $fullColumnName;
                    }
                }
            }

            //Get all the PKs in the table (ie all the lines to update), and update the FKs with random values
            $pkValues = $this->getPkRegistry($table)->loadValuesFromTable()->getAllValues();
            foreach ($pkValues as $pkValue){
                $newValues = [];
                foreach ($indexes as $index){
                    /** @var Index $index */
                    $compoundColumnGenerator = $this->getCompoundColumnGenerator($table, $index, \count($pkValues));
                    $newValues = array_merge($newValues, $compoundColumnGenerator());
                }
                $this->connection->update($tableName, $newValues, $pkValue);
            }
        }


        return $handledColumns;
    }

    /**
     * @param string[] $handledColumns
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateRemainingForeignKeys(array $handledColumns): void
    {
        foreach ($this->foreignKeyStore as $tableName => $fks){
            if (!array_key_exists($tableName, $this->fakeTableRowNumbers)){
                //only update tables where data has been inserted
                continue;
            }
            $table = $this->schemaManager->listTableDetails($tableName);


            //Get all the PKs in the table (ie all the lines to update), and update the FKs with random PK values
            $pkValues = $this->getPkRegistry($table)->loadValuesFromTable()->getAllValues();
            foreach ($pkValues as $pkValue){
                $newValues = [];
                foreach ($fks as $fk) {
                    $foreignTable = $this->schemaManager->listTableDetails($fk->getForeignTableName());
                    $localColums = $fk->getLocalColumns();
                    foreach ($localColums as $index => $localColumn) {
                        if (\in_array($tableName.".".$localColumn, $handledColumns, true)){
                            continue;
                        }
                        $column = $table->getColumn($localColumn);
                        $fkValueGenerator = $this->getForeignKeyColumnGenerator($table, $column, $foreignTable);
                        $newValues[$localColumn] = $fkValueGenerator();
                    }
                }
                $this->connection->update($tableName, $newValues, $pkValue);
            }
        }
    }

    /**
     * @param Table $table
     * @param Index $index
     * @return CompoundColumnGenerator
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     */
    private function getCompoundColumnGenerator(Table $table, Index $index, int $count): CompoundColumnGenerator
    {
        if (!isset($this->compoundColumnGenerators[$table->getName() . "." . $index->getName()])){
            $compoundGenerator = new CompoundColumnGenerator($table, $index, $this->schemaHelper, $this, $this->schemaManager, $count);
            $this->compoundColumnGenerators[$table->getName() . '.' . $index->getName()] = $compoundGenerator;
        }
        return $this->compoundColumnGenerators[$table->getName() . '.' . $index->getName()];
    }

    /**
     * @param Table $table
     * @param Column $column
     * @param Table $foreignTable
     * @return ForeignKeyColumnGenerator
     * @throws \DBFaker\Exceptions\SchemaLogicException
     */
    public function getForeignKeyColumnGenerator(Table $table, Column $column, Table $foreignTable): ForeignKeyColumnGenerator
    {
        $identifier = $table->getName() . '.' . $column->getName();
        if (!isset($this->fkColumnsGenerators[$identifier])){
            $fkGenerator = new ForeignKeyColumnGenerator($table, $column, $this->getPkRegistry($foreignTable), new DBFakerSchemaManager($this->schemaManager));
            $this->fkColumnsGenerators[$identifier] = $fkGenerator;

        }
        return $this->fkColumnsGenerators[$identifier];
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return FakeDataGeneratorInterface
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     */
    public function getSimpleColumnGenerator(Table $table, Column $column) : FakeDataGeneratorInterface
    {
        return $this->generatorFinder->findGenerator($table, $column);
    }

}
