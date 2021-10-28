<?php

namespace ScannedTest\Interfaces;

use Shared\Annotation\GeneratedClass;

#[GeneratedClass]
interface InterfaceForGeneration
{
    public function processValue(int $value): int;
}