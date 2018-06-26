<?php
namespace DBFaker\Generators;

interface FakeDataGeneratorInterface
{
    /**
     * @return mixed
     */
    public function __invoke();

}