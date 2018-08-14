<?php
namespace DBFaker\Generators;

use DBFaker\Exceptions\FileTooLargeException;
use DBFaker\Exceptions\NoTestFilesFoundException;
use Doctrine\DBAL\Schema\Column;

class BlobGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var string
     */
    private $globExpression;
    /**
     * @var Column
     */
    private $column;

    public function __construct(string $globExpression, Column $column)
    {
        $this->globExpression = $globExpression;
        $this->column = $column;
    }

    /**
     * @return bool|resource
     * @throws NoTestFilesFoundException
     */
    public function __invoke()
    {
        $files = glob($this->globExpression, GLOB_MARK);
        $files = array_filter($files, function ($fileName) {
            return strrpos($fileName, DIRECTORY_SEPARATOR) !== \strlen($fileName) - 1;
        });
        foreach ($files as $file) {
            // Note: length = 0 for Blob types (unlimited) but not for Binary types (limited in length)
            if ($this->column->getLength() !== 0 && \filesize($file) > $this->column->getLength()) {
                throw FileTooLargeException::create($file, $this->column);
            }
        }
        if (\count($files) === 0) {
            throw new NoTestFilesFoundException("No files found for glob expression '".$this->globExpression."'");
        }
        $files = array_values($files);
        $chosenFile = $files[random_int(0, \count($files) - 1)];

        // TODO: throw exception if column not large enough

        return fopen($chosenFile, 'rb');
    }
}
