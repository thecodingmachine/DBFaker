<?php
namespace DBFaker\Generators;

use DBFaker\Exceptions\NoTestFilesFoundException;

class BlobGenerator implements FakeDataGeneratorInterface
{

    /**
     * @var string
     */
    private $globExpression;

    public function __construct(string $globExpression)
    {
        $this->globExpression = $globExpression;
    }

    /**
     * @return bool|resource
     * @throws NoTestFilesFoundException
     */
    public function __invoke()
    {
        $files = glob($this->globExpression, GLOB_MARK);
        $files = array_filter($files, function ($fileName){
            return strrpos($fileName, DIRECTORY_SEPARATOR) !== \strlen($fileName) - 1;
        });
        if (\count($files) === 0){
            throw new NoTestFilesFoundException("No files found for glob expression '".$this->globExpression."'");
        }
        $files = array_values($files);
        $chosenFile = $files[random_int(0, \count($files) - 1)];
        return fopen($chosenFile, 'rb');
    }


}