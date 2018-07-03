<?php
namespace DBFaker;

use DBFaker\Exceptions\DBFakerException;
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
use Doctrine\DBAL\Types\Type;
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
     * @var PrimaryKeyRegistry[]
     */
    private $primaryKeyRegistries = [];

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

    /**
     * @var string[]
     */
    private $handledFKColumns = [];

    /**
     * @var DBFakerSchemaManager
     */
    private $fakerManagerHelper;

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
        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->schemaManager = $this->connection->getSchemaManager();
        $this->schemaHelper = new SchemaHelper($schema);
        $this->fakerManagerHelper = new DBFakerSchemaManager($this->schemaManager);
    }

    /**
     * Main function : does all the job
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     * @throws \DBFaker\Exceptions\SchemaLogicException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \DBFaker\Exceptions\DBFakerException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fakeDB() : void
    {
        set_time_limit(0);//Import may take a looooooong time :)
        $data = $this->generateFakeData();
        $extensionContraints = $this->getExtensionConstraints();
        $foreignKeys = $this->dropForeignKeys();
        $multipleUniqueContraints = $this->dropMultipleUniqueContraints();
        $this->insertFakeData($data, $extensionContraints, $multipleUniqueContraints, $foreignKeys);
        $this->restoreForeignKeys($foreignKeys);
        $this->restoreMultipleUniqueContraints($multipleUniqueContraints);
    }

    /**
     * Generates the fake data for specified tables
     * @return mixed[]
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     * @throws \DBFaker\Exceptions\PrimaryKeyColumnMismatchException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function generateFakeData() : array
    {
        $this->log->info("Step 1 : Generating data ...");

        $data = [];
        foreach ($this->fakeTableRowNumbers as $tableName => $nbLines) {
            $table = $this->schemaManager->listTableDetails($tableName);
            $data[$table->getName()] = $this->getFakeDataForTable($table, $nbLines);
        }

        return $data;
    }

    /**
     * @param Table $table the table for which fake data will be generated
     * @param int $nbLines : the number of lines to generate
     * @return mixed[]
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     * @throws \DBFaker\Exceptions\PrimaryKeyColumnMismatchException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
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
     * @param $data
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function insertFakeData($data, $extensionContraints, $multipleUniqueContraints, $foreignKeys) : void
    {
        //1 - First insert data with no FKs, and null PKs. This will generate primary keys
        $this->log->info('Step 3.1 : Insert simple data ...');
        $this->insertWithoutFksAndUniqueIndexes($data);

        //2 - loop on multiple unique index constraints (that may include FKs)
        $this->log->info('Step 3.2 : Update Multiple Unique Indexed Columns');
        $handledColumns = $this->updateExtensionContraints($extensionContraints);

        //2 - loop on multiple unique index constraints (that may include FKs)
        $this->log->info('Step 3.3 : Update Multiple Unique Indexed Columns');
        $handledColumns = $this->updateMultipleUniqueIndexedColumns($multipleUniqueContraints, $handledColumns);

        //3 - loop again to set FKs now that all PK have been loaded
        $this->log->info('Step 3.4 : Update Remaining ForeignKeys');
        $this->updateRemainingForeignKeys($foreignKeys, $handledColumns);
    }

    /**
     * Inserts base data :
     *    - AutoIncrement PKs will be generated and stored
     *    - ForeignKey and Multiple Unique Indexes are ignored, because we need self-generated PK values
     * @param mixed[] $data
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function insertWithoutFksAndUniqueIndexes($data): void
    {
        $plateform = $this->connection->getDatabasePlatform();
        foreach ($data as $tableName => $rows){
            $table = $this->schemaManager->listTableDetails($tableName);

            //initiate column types for insert : only get the first array to retrieve column names
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
            $cnt = count($rows);
            foreach ($rows as $index => $row){
                $dbRow = [];
                foreach ($row as $columnName => $value){
                    $column = $table->getColumn($columnName);
                    $newVal = $column->getType()->convertToDatabaseValue($value, $plateform);
                    $dbRow[$column->getQuotedName($this->connection->getDatabasePlatform())] = $newVal;
                }
                $this->log->info("Step 3.1 : Inserted $index of $cnt in $tableName");
                $this->connection->insert($table->getName(), $dbRow, $types);
            }
            //if autoincrement, add the new ID to the PKRegistry
            if ($table->hasPrimaryKey()){
                $pkColumnName = $table->getPrimaryKeyColumns()[0];
                $pkColumn = $table->getColumn($pkColumnName);
                if ($pkColumn->getAutoincrement() && $this->schemaHelper->isPrimaryKeyColumn($table, $pkColumn)){
                    $this->getPkRegistry($table)->addValue([$pkColumnName => $this->connection->lastInsertId()]);
                }
            }
        }
    }

    /**
     * @param Table $table
     * @return PrimaryKeyRegistry
     * @throws \Doctrine\DBAL\DBALException
     * @throws \DBFaker\Exceptions\SchemaLogicException
     */
    public function getPkRegistry(Table $table, $isSelfReferencing = false) : PrimaryKeyRegistry
    {
        $index = $table->getName().($isSelfReferencing ? 'dbfacker_self_referencing' :'');
        if (!isset($this->primaryKeyRegistries[$index])) {
            $this->primaryKeyRegistries[$index] = new PrimaryKeyRegistry($this->connection, $table, $this->schemaHelper, $isSelfReferencing);
        }
        return $this->primaryKeyRegistries[$index];
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
     * @throws \Doctrine\DBAL\DBALException
     * @throws \DBFaker\Exceptions\SchemaLogicException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @return mixed[]
     */
    private function dropForeignKeys() : array
    {
        $this->log->info('Step 2.1 : Drop FKs ...');
        $foreignKeys = [];
        $tables = $this->schemaManager->listTables();
        foreach ($tables as $table){
            foreach ($table->getForeignKeys() as $fk){
                $foreignTable = $this->schemaManager->listTableDetails($fk->getForeignTableName());
                foreach ($fk->getColumns() as $localColumnName){
                    $localColumn = $table->getColumn($localColumnName);
                    $selfReferencing = $fk->getForeignTableName() === $table->getName();
                    $fkValueGenerator = new ForeignKeyColumnGenerator($table, $localColumn, $this->getPkRegistry($foreignTable, $selfReferencing), $fk, $this->fakerManagerHelper, $this->schemaHelper);
                    $this->fkColumnsGenerators[$table->getName() . '.' . $localColumnName] = $fkValueGenerator;
                }
                $foreignKeys[$table->getName()][] = $fk;
                $this->schemaManager->dropForeignKey($fk, $table);
            }
        }
        return $foreignKeys;
    }

    /**
     * Restore the foreign keys based on the ForeignKeys store built when calling dropForeignKeys()
     * @param mixed $foreignKeys
     */
    private function restoreForeignKeys($foreignKeys) : void
    {
        $this->log->info('Step 4 : restore foreign keys');
        foreach ($foreignKeys as $tableName => $fks){
            foreach ($fks as $fk){
                $this->schemaManager->createForeignKey($fk, $tableName);
            }
        }
    }

    /**
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    private function dropMultipleUniqueContraints(): array
    {
        $this->log->info('Step 2.2 : Drop Multiple indexes ...');
        $multipleUniqueContraints = [];
        $tables = $this->schemaManager->listTables();
        foreach ($tables as $table){
            foreach ($table->getIndexes() as $index){
                if ($index->isUnique() && count($index->getColumns()) > 1){
                    $multipleUniqueContraints[$table->getName()][] = $index;
                    $this->schemaManager->dropIndex($index->getQuotedName($this->connection->getDatabasePlatform()), $table->getName());
                }
            }
        }
        return $multipleUniqueContraints;
    }

    /**
     * @param mixed[] $multipleUniqueContraints
     */
    private function restoreMultipleUniqueContraints($multipleUniqueContraints): void
    {
        $this->log->info('Step 5 : restore multiple unique indexes keys');
        foreach ($multipleUniqueContraints as $tableName => $indexes){
            foreach ($indexes as $index){
                $this->schemaManager->createIndex($index, $tableName);
            }
        }
    }

    /**
     *
     * Sets data for a group of columns (2 or more) that are bound by a unique constraint
     * @param mixed[] $multipleUniqueContraints
     * @param string[] $handledFKColumns
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function updateMultipleUniqueIndexedColumns($multipleUniqueContraints, $handledFKColumns) : array
    {

        foreach ($multipleUniqueContraints as $tableName => $indexes){
            $table = $this->schemaManager->listTableDetails($tableName);

            foreach ($indexes as $index){
                foreach ($index->getColumns() as $columnName){
                    $fullColumnName = $tableName. '.' .$columnName;
                    if (!\in_array($fullColumnName, $handledFKColumns, true)){
                        $handledFKColumns[] = $fullColumnName;
                    }
                }
            }

            $stmt = $this->connection->query('SELECT * FROM ' .$tableName);
            $count = $this->connection->fetchColumn('SELECT count(*) FROM ' .$tableName);
            $i = 1;
            while ($row = $stmt->fetch()) {
                $newValues = [];
                foreach ($indexes as $index){
                    /** @var Index $index */
                    $compoundColumnGenerator = $this->getCompoundColumnGenerator($table, $index, $count);
                    $newValues = array_merge($newValues, $compoundColumnGenerator());
                }
                $this->connection->update($tableName, $newValues, $this->stripUnselectableColumns($table, $row));
                $this->log->info("Updated $i of $count for $tableName");
                $i++;
            }
        }
        return $handledFKColumns;
    }

    /**
     * @param mixed[] $foreignKeys
     * @param string[] $handledFKColumns
     * @throws \DBFaker\Exceptions\DBFakerException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function updateRemainingForeignKeys($foreignKeys, $handledFKColumns): void
    {
        foreach ($foreignKeys as $tableName => $fks){
            if (!array_key_exists($tableName, $this->fakeTableRowNumbers)){
                //only update tables where data has been inserted
                continue;
            }

            $table = $this->schemaManager->listTableDetails($tableName);

            $stmt = $this->connection->query('SELECT * FROM ' .$tableName);
            $count = $this->connection->fetchColumn("SELECT count(*) FROM ".$tableName);
            $i = 1;
            while ($row = $stmt->fetch()) {
                $newValues = [];
                foreach ($fks as $fk) {
                    $localColumns = $fk->getLocalColumns();
                    foreach ($localColumns as $index => $localColumn) {
                        if (\in_array($tableName . '.' . $localColumn, $handledFKColumns)){
                            continue;
                        }
                        $column = $table->getColumn($localColumn);
                        $fkValueGenerator = $this->getForeignKeyColumnGenerator($table, $column);
                        $newValues[$localColumn] = $fkValueGenerator();
                    }
                }
                $row = $this->stripUnselectableColumns($table, $row);
                if (count($newValues) && $this->connection->update($tableName, $newValues, $row) === 0){
                    throw new DBFakerException("Row has not been updated $tableName - ". var_export($newValues,true) . ' - ' . var_export($row, true));
                };
                $this->log->info("Step 3.3 : updated $i of $count for $tableName");
                $i++;
            }
        }
    }

    /**
     * @param mixed[] $extensionConstraints
     * @return string[]
     * @throws \DBFaker\Exceptions\DBFakerException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Exception
     */
    private function updateExtensionContraints($extensionConstraints) : array
    {
        $handledFKColumns = [];
        foreach ($extensionConstraints as $fk){
            $localTableName = $fk->getLocalTable()->getQuotedName($this->connection->getDatabasePlatform());
            $stmt = $this->connection->query('SELECT * FROM ' . $localTableName);
            $count = $this->connection->fetchColumn('SELECT count(*) FROM ' .$localTableName);
            $i = 1;
            while ($row = $stmt->fetch()) {
                $newValues = [];
                $localColumns = $fk->getLocalColumns();
                foreach ($localColumns as $index => $localColumn) {
                    $handledFKColumns[] = $fk->getLocalTable()->getName() . '.' . $localColumn;
                    $column = $fk->getLocalTable()->getColumn($localColumn);
                    $fkValueGenerator = $this->getForeignKeyColumnGenerator($fk->getLocalTable(), $column);
                    $newValues[$localColumn] = $fkValueGenerator();
                }
                $row = $this->stripUnselectableColumns($fk->getLocalTable(), $row);
                if ($this->connection->update($localTableName, $newValues, $row) === 0){
                    throw new DBFakerException("Row has not been updated $localTableName - ". var_export($newValues,true) . ' - ' . var_export($row, true));
                };
                $this->log->info("Updated $i of $count for $localTableName");
                $i++;
            }
        }
        return $handledFKColumns;
    }

    /**
     * @param Table $table
     * @param Index $index
     * @return CompoundColumnGenerator
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \DBFaker\Exceptions\SchemaLogicException
     * @throws \DBFaker\Exceptions\UnsupportedDataTypeException
     */
    private function getCompoundColumnGenerator(Table $table, Index $index, int $count): CompoundColumnGenerator
    {
        if (!isset($this->compoundColumnGenerators[$table->getName() . "." . $index->getName()])){
            $compoundGenerator = new CompoundColumnGenerator($table, $index, $this->schemaHelper, $this, $this->schemaManager, $this->fakerManagerHelper, $count);
            $this->compoundColumnGenerators[$table->getName() . '.' . $index->getName()] = $compoundGenerator;
        }
        return $this->compoundColumnGenerators[$table->getName() . '.' . $index->getName()];
    }

    /**
     * @param Table $table
     * @param Column $column
     * @return ForeignKeyColumnGenerator
     * @throws \DBFaker\Exceptions\SchemaLogicException
     */
    public function getForeignKeyColumnGenerator(Table $table, Column $column): ForeignKeyColumnGenerator
    {
        $identifier = $table->getName() . '.' . $column->getName();
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
        return $this->generatorFinder->findGenerator($table, $column, $this->schemaHelper);
    }

    /**
     * @param Table $table
     * @param array $row
     * @return mixed[]
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function stripUnselectableColumns(Table $table, array $row) : array
    {
        return array_filter($row, function(string $columnName) use ($table) {
            return !\in_array($table->getColumn($columnName)->getType()->getName(), [
                Type::BINARY, Type::JSON, Type::JSON_ARRAY, Type::SIMPLE_ARRAY, Type::TARRAY, Type::BLOB, Type::JSON, Type::OBJECT
            ],true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @return ForeignKeyConstraint[]
     */
    private function getExtensionConstraints() : array
    {
        $this->log->info('Step 2.1 : store extension constraints ...');
        $extensionConstraints = [];
        $tables = $this->schemaManager->listTables();
        foreach ($tables as $table){
            foreach ($table->getForeignKeys() as $fk){
                if ($this->schemaHelper->isExtendingKey($fk)){
                    $extensionConstraints[] = $fk;
                }
            }
        }
        return $extensionConstraints;
    }

}
