[![Latest Stable Version](https://poser.pugx.org/thecodingmachine/DBFaker/v/stable)](https://packagist.org/packages/thecodingmachine/DBFaker)
[![Total Downloads](https://poser.pugx.org/thecodingmachine/DBFaker/downloads)](https://packagist.org/packages/thecodingmachine/DBFaker)
[![Latest Unstable Version](https://poser.pugx.org/thecodingmachine/DBFaker/v/unstable)](https://packagist.org/packages/thecodingmachine/DBFaker)
[![License](https://poser.pugx.org/thecodingmachine/DBFaker/license)](https://packagist.org/packages/thecodingmachine/DBFaker)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/DBFaker/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/thecodingmachine/DBFaker/?branch=master)
[![Build Status](https://travis-ci.org/thecodingmachine/DBFaker.svg?branch=master)](https://travis-ci.org/thecodingmachine/DBFaker)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/DBFaker/badge.svg?branch=master&service=github)](https://coveralls.io/github/thecodingmachine/DBFaker?branch=master)

# DBFakeFiller
An easy to use tool to popuplate your database with fake data. Based on [doctrine/dbal](https://github.com/doctrine/dbal) so it should me cross plateforme. Fake data are generated using [fzaninotto/faker](https://github.com/fzaninotto/Faker).

This tool will parse your database schema, generate fake data and insert it inside your database. In order to generate the most valuable data, it will look into:
* columns' types (INT, VARCHAR, TEXT, TIMESTAMP, etc.)
* columns' attributes (precision, scale, nullable, unsigned, etc.)
* tables' relations to create valid relation ships (obvious, else the constraints would break)

> **WARNING :** Always backup your database **before** running the DBFaker in case it breaks !</span>

## Install

`composer require thecodingmachine/DBFaker`

## Minimalist usage
```php
//instanciate the DBAL connectioin
$connectionParams = [
    "host" => "localhost",
    "user" => "DBU USER",
    "password" => "DB HOST",
    "port" => null,
    "dbname" => "YOUR DB NAME",
    "charset" => "utf8",
    "driverOptions" => array(
        1002 =>"SET NAMES utf8"
    )
];
$conn = new \Doctrine\DBAL\Connection($connectionParams, new Doctrine\DBAL\Driver\PDOMySql\Driver(), null, new \Doctrine\Common\EventManager());

$faker = new \DBFaker\DBFaker($conn);

//say what tables should be filled (other tables will be considered as reference table or at least already filled)
$faker->setFakeTableRowNumbers([
    "table1" => 300, //generate 300 lines for table 1
    "table2" => 20,
    //...
]);
$faker->fakeDB();
```
... now have a look at you database :) nice isn't it ?
 
## Advanced usage
### Custom data generators
By default, fake data is generated regarding the information retrieved about the column. You can pass a ```GeneratorFactory``` instance to DBFaker's
constructor in order to specify a custom ```FakeDataGeneratorInterface```.
```php
$generatorFactory = new \DBFaker\Generators\GeneratorFactory();

/* 
Don't hesitate to use the Faker package to generate random data,
there is plenty of data types available (IBAN, address, country code, ...).
You can evenpass the locale to generate localized data !
*/
$generator = \Faker\Factory::create();

//... add your custom generators ...
//... and injectc the Generator Factory
$faker = new \DBFaker\DBFaker($conn, $generatorFactory);
);
```

#### Customize for column
You may use the ```GeneratorFactory::setGeneratorForColumn()``` to set a specific generator for a given column :
```php
// address.postal_code column is a varchar, so default generated data will be text. Here we want a postal code :
$generatorFactory->setGeneratorForColumn(
    "address", "postal_code", function() use ($generator){ return $generator->postcode; }
);
```

```setGeneratorForColumn``` takes 3 arguments : ```string $tableName``` and ```string $ColumnName``` to specify which the column and the third argument
can be either :
 * a ```callable``` that takes the ```\Doctrine\DBAL\Schema\Column $column``` as input parameter,
 * a ```DBFaker\Generators\FakeDataGeneratorInterface``` instance

### Customize by condition
For more flexible customization, you can use ```GeneratorFactory::setGeneratorForColumn()```.
```php
// all columns that end with "_email" or are named exactly "email" should be emails
$generatorFactory->setGenerator(
    function(\Doctrine\DBAL\Schema\Column $column){
        return preg_match("/([(.*_)|_|]|^)email$/", $column->getName()) === 1;
    }, function() use ($generator){
        return $generator->email;
    }
);
```
This time, first argument is a ```callable``` that takes the ```\Doctrine\DBAL\Schema\Column $column``` as input parameter and should return a
```boolean``` (```true``` if override should happend). The Second argument is the same as for the previous ```setGeneratorForColumn``` function.
 
### Set null probability
