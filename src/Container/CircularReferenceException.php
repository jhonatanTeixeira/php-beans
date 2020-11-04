<?php


namespace PhpBeans\Container;

class CircularReferenceException extends ContainerException
{
    private string $name;
    
    private string $onClass;
    
    public function __construct(string $name, string $onClass) {
        $this->name = $name;
        $this->onClass = $onClass;
        
        parent::__construct(sprintf('circular dependency on class %s depends on %s', $onClass, $name));
    }

}
