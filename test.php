<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 02/06/17
 * Time: 19:03
 */
require_once "vendor/autoload.php";
$pattern = "/([(.*_)|_|]|^)email$/";
$test = preg_match($pattern, "email");
var_dump($test);
$test = preg_match($pattern, "_email");
var_dump($test);
$test = preg_match($pattern, "user_email");
var_dump($test);
$test = preg_match($pattern, "useremail");
var_dump($test);
$test = preg_match($pattern, "fail");
var_dump($test);
exit;

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

$generatorFactory = new \DBFaker\Generators\GeneratorFactory();

/* Don't hesitate to use the Faker package to generate random data,
   there is plenty of data types available (IBAN, address, country code, ...).
*/
$generator = \Faker\Factory::create();//you could pass the locale to generate localized data !

// address.postal_code column is a varchar, so default generated data will be text. Here we want a postal code :
$generatorFactory->setGeneratorForColumn(
    "address", "postal_code", function() use ($generator){ return $generator->postcode; }
);

// all columns that end with "_email" or are named exactly "email" should be emails
$generatorFactory->setGenerator(
    function(\Doctrine\DBAL\Schema\Column $column){
        return preg_match("/([(.*_)|_|]|^)email$/", $column->getName()) === 1;
    }, function() use ($generator){
        return $generator->email;
    }
);

$faker = new \DBFaker\DBFaker($conn, $generatorFactory);
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