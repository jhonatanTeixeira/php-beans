<?php

namespace PhpBeans\Processor;

use PhpBeans\Container\Container;
use PhpBeans\Container\ContainerAwareInterface;

abstract class AbstractStereotypeProcessor implements ContainerAwareInterface
{
    private Container $container;
    
    public function setContainer(Container $container): void {
        $this->container = $container;
    }

    public function getContainer(): Container {
        return $this->container;
    }

    protected function fetchStereotypes() {
        return $this->container->getBeansByComponent($this->getStereotypeName());
    }
    
    abstract public function getStereotypeName(): string;
    
    public function findAndProcess() {
        foreach ($this->fetchStereotypes() as $stereotype) {
            $this->process($stereotype);
        }
    }
    
    abstract public function process($stereotype);
}
