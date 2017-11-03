<?php
require_once "vendor/autoload.php";

$connectionParams = [
    "host" => "localhost",
    "user" => "root",
    "password" => "root",
    "port" => null,
    "dbname" => "timemachine_test",
    "charset" => "utf8",
    "driverOptions" => array(
        1002 =>"SET NAMES utf8"
    )
];
$conn = new \Doctrine\DBAL\Connection($connectionParams, new Doctrine\DBAL\Driver\PDOMySql\Driver(), null, new \Doctrine\Common\EventManager());

$generatorFinderBuilder = \DBFaker\Generators\GeneratorFinderBuilder::buildDefaultFinderBuilder();

/* Don't hesitate to use the Faker package to generate random data,
   there is plenty of data types available (IBAN, address, country code, ...).
*/
$dataFaker = \Faker\Factory::create();//you could pass the locale to generate localized data !

// address.postal_code column is a varchar, so default generated data will be text. Here we want a postal code :
$generatorFinderBuilder->addGenerator(
    new \DBFaker\Generators\Conditions\CallBackCondition(function(\Doctrine\DBAL\Schema\Table $table,  \Doctrine\DBAL\Schema\Column $column){
        return $table->getName() == "address" && $column->getName() == "postal_code";
    }),
    function() use ($dataFaker){
        return $dataFaker->postcode
    }
);

// all columns that end with "_email" or are named exactly "email" should be emails
$generatorFinderBuilder->addGenerator(
    new \DBFaker\Generators\Conditions\CallBackCondition(function(\Doctrine\DBAL\Schema\Table $table,  \Doctrine\DBAL\Schema\Column $column){
        return preg_match("/([(.*_)|_|]|^)email$/", $column->getName()) === 1;
    }),
    function() use ($dataFaker){
        return $dataFaker->email;
    }
);

$faker = new \DBFaker\DBFaker($conn, $generatorFinderBuilder->buildFinder());
$faker->setFakeTableRowNumbers([
    "a_user_project" => 300,
    "bill" => 200,
    "client" => 10,
    "community_spending" => 100,
    "cron_flag" => 10,
    "project" => 50,
    "project_note" => 200 ,
    "project_steps" => 10,
    "project_tasks" => 35,
    "request_hollidays" => 20,
    "request_hollidays_log" => 200,
    "request_hollidays_projects" => 240,
    "time" => 30,
    "user" => 40,
    "user_right" => 160,
    "validate_week" => 0
]);
$faker->fakeDB();