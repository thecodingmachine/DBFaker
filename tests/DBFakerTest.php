<?php
namespace DBFaker;

use DBFaker\Generators\BlobGenerator;
use DBFaker\Generators\BlobGeneratorFactory;
use DBFaker\Generators\Conditions\CallBackCondition;
use DBFaker\Generators\Conditions\CheckTypeCondition;
use DBFaker\Generators\SimpleGenerator;
use DBFaker\Generators\SimpleGeneratorFactory;
use DBFaker\Helpers\SchemaHelper;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use TheCodingMachine\FluidSchema\DefaultNamingStrategy;
use TheCodingMachine\FluidSchema\FluidSchema;

class DBFakerTest extends TestCase
{

    public static function setUpBeforeClass()
    {
        $adminConn = self::getAdminConnection();
        $adminConn->getSchemaManager()->dropAndCreateDatabase($GLOBALS['db_name']);

        $dbConnection = self::getConnection();
        $dbalSchema = $dbConnection->getSchemaManager()->createSchema();
        $namingStrategy = new DefaultNamingStrategy();

        $db = new FluidSchema($dbalSchema, $namingStrategy);
        $fromSchema = clone $dbalSchema;

        $persons = $db->table("persons")
            ->id()
            ->column("email")->string(50)->unique()
            ->column("password")->string(100)->then()
            ->timestamps();

        $users = $db->table("users")
            ->extends("persons")
            ->column("lastname")->string(50)
            ->column("firstname")->string(50)
            ->column("password")->string(200)
            ->column("uuid")->guid()->null()
            ->column("parent_id")->references("users")
            ->column("birth_date")->date()
            ->column("access_level")->smallInt()
            ->column("last_access")->datetime();


        $roles = $db->table("roles")
            ->id()
            ->column("label")->string(20);

        $db->junctionTable("users", "roles");
        $usersRolesTableName = $namingStrategy->getJointureTableName("users", "roles");

        $countries = $db->table("countries")
            ->id()
            ->column("name")->string(100)
            ->column("population")->bigInt()
            ->column("birthrate")->float()
            ->column("president_id")->references("persons")
            ->column("population_density")->decimal(10,2)
            ->column("summary")->text();

        $users->column("country_id")->references("countries");

        $regions = $db->table("regions")
            ->id()
            ->column("country_id")->references("countries")
            ->column("name")->string("50")
            ->column("is_active")->boolean()
            ->column("binary")->binary()
            ->column("datetimeTz")->datetimeTz()
            ->column("time")->time()
            ->column("dateImmutable")->dateImmutable()
            ->column("datetimeImmutable")->datetimeImmutable()
            ->column("datetimeTzImmutable")->datetimeTzImmutable()
            ->column("timeImmutable")->timeImmutable()
            ->column("dateInterval")->dateInterval()
            ->column("blob")->blob()
            ->column("array")->array()
            ->column("json")->json()
            ->column("jsonArray")->jsonArray()
            ->column("object")->object();

        $sqlStmts = $dbalSchema->getMigrateFromSql($fromSchema, $dbConnection->getDatabasePlatform());
        foreach ($sqlStmts as $sqlStmt) {
            $dbConnection->exec($sqlStmt);
        }

        $dbConnection->insert("roles", ["label" => "ADMIN"]);
        $dbConnection->insert("roles", ["label" => "SIMPLE USER"]);

    }

    public function testFaker()
    {
        $conn = self::getConnection();

        $generatorFinderBuilder = \DBFaker\Generators\GeneratorFinderBuilder::buildDefaultFinderBuilder();

        /* Don't hesitate to use the Faker package to generate random data,
           there is plenty of data types available (IBAN, address, country code, ...).
        */
//        $dataFaker = \Faker\Factory::create();//you could pass the locale to generate localized data !

        // address.postal_code column is a varchar, so default generated data will be text. Here we want a postal code :
        $generatorFinderBuilder->addGenerator(
            new \DBFaker\Generators\Conditions\CallBackCondition(function(\Doctrine\DBAL\Schema\Table $table,  \Doctrine\DBAL\Schema\Column $column){
                return $table->getName() == "address" && $column->getName() == "postal_code";
            }),
            new SimpleGeneratorFactory("postcode")
        );

        // all columns that end with "_email" or are named exactly "email" should be emails
        $generatorFinderBuilder->addGenerator(
            new CallBackCondition(function(\Doctrine\DBAL\Schema\Table $table,  \Doctrine\DBAL\Schema\Column $column){
                return preg_match("/([(.*_)|_|]|^)email$/", $column->getName()) === 1;
            }),
            new SimpleGeneratorFactory("email")
        );

        $generatorFinderBuilder->addGenerator(
            new CheckTypeCondition(Type::getType(Type::BLOB)),
            new BlobGeneratorFactory(__DIR__ . "/fixtures/*")
        );
        $generatorFinderBuilder->addGenerator(
            new CheckTypeCondition(Type::getType(Type::BINARY)),
            new BlobGeneratorFactory(__DIR__ . "/fixtures/*")
        );

        $faker = new \DBFaker\DBFaker($conn, $generatorFinderBuilder->buildFinder());

        $tableRowNumbers = [
            "users" => 20,
            "persons" => 30,
            "users_roles" => 30,
            "countries" => 10,
            "regions" => 3

        ];
        $faker->setFakeTableRowNumbers($tableRowNumbers);
        $faker->fakeDB();

        foreach ($tableRowNumbers as $tableName => $expectedCount){
            $count = $conn->fetchColumn('SELECT count(*) from ' . $tableName);
            $this->assertEquals($expectedCount, $count);
        }
    }

    private static function getAdminConnection()
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'user' => $GLOBALS['db_username'],
            'password' => $GLOBALS['db_password'],
            'host' => $GLOBALS['db_host'],
            'port' => $GLOBALS['db_port'],
            'driver' => $GLOBALS['db_driver'],
        );

        return DriverManager::getConnection($connectionParams, $config);
    }

    private static function getConnection()
    {
        $connectionParams = array(
            'user' => $GLOBALS['db_username'],
            'password' => $GLOBALS['db_password'],
            'host' => $GLOBALS['db_host'],
            'port' => $GLOBALS['db_port'],
            'driver' => $GLOBALS['db_driver'],
            'dbname' => $GLOBALS['db_name'],
            "charset" => "utf8",
            "driverOptions" => array(
                1002 =>"SET NAMES utf8"
            )
        );

        return new Connection($connectionParams, new Driver(), null, new EventManager());
    }



}
