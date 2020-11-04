<?php

namespace PhpBeans\Container;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundContainerException extends ContainerException implements NotFoundExceptionInterface
{
    public function __construct(string $name, string $type = 'none') {
        parent::__construct("Bean with name $name and type $type not registered");
    }
    
    public static function trigger(string $name) {
        throw new self($name);
    }
}
