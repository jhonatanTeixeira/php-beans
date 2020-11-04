<?php

namespace PhpBeans\Container;

interface ContainerWriterInterface 
{
    public function set(string $id, $value);
}
