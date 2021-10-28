<?php

namespace PhpBeans\Container;

trait ContainerAwareTrait
{
    private Container $container;

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}