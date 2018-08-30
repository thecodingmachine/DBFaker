<?php
namespace DBFaker\Exceptions;


use Doctrine\DBAL\Schema\Column;

class FileTooLargeException extends \OutOfRangeException
{
    public static function create(string $path, Column $column): self
    {
        return new self(sprintf('File %s is too large. File size: %d. Maximum column size: %d.', $path, \filesize($path), $column->getLength()));
    }

}