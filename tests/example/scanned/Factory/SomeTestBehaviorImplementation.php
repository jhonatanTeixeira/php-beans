<?php


namespace ScannedTest\Factory;


class SomeTestBehaviorImplementation implements SomeTestBehavior
{
    public function isBehavior(): bool
    {
        return true;
    }
}