<?php


namespace PhpBeansTest\Factory;


class SomeTestBehaviorImplementation implements SomeTestBehavior
{
    public function isBehavior(): bool
    {
        return true;
    }
}