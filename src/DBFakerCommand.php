<?php
namespace DBFaker;


use DBFaker\Generators\ComplexObjectGenerator;
use DBFaker\Generators\GeneratorFactory;
use DBFaker\Helpers\PrimaryKeyRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Faker\Generator;
use Mouf\Database\SchemaAnalyzer\SchemaAnalyzer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DBFakerCommand extends Command
{

    /**
     * @var DBFaker
     */
    private $dbFaker;

    /**
     * DBFaker constructor.
     * @param Connection $connection
     * @param GeneratorFactory $generatorFactory
     * @param SchemaAnalyzer $schemaAnalyzer
     */
    public function __construct(DBFaker $faker)
    {
        parent::__construct();
        $this->dbFaker = $faker;
    }

    protected function configure()
    {
        $this
            ->setName('dbfaker:fake-data')
            ->setDescription('Generates fake data related to database\'s structure and populates the database')
            ->setHelp('This command allows you to create a user...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //TODO : how to pass output for detailed logging ?
        $this->dbFaker->fakeDB();
    }
}