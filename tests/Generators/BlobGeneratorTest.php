<?php

namespace DBFaker\Generators;

use DBFaker\Exceptions\FileTooLargeException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class BlobGeneratorTest extends TestCase
{

    public function testFileTooLarge()
    {
        $column = new Column('foo', Type::getType(Type::BINARY));
        $column->setLength(255);
        $blobGenerator = new BlobGenerator(__DIR__.'/../fixtures/blob/*.png', $column);
        $this->expectException(FileTooLargeException::class);
        $blobGenerator->__invoke();
    }
}
