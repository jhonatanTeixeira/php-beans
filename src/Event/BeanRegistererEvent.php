<?php

namespace PhpBeans\Event;

use PhpBeans\Bean\BeanRegisterer;
use PhpBeans\Container\Container;

class BeanRegistererEvent 
{
    private Container $container;
    
    private BeanRegisterer $beanRegisterer;
    
    public function __construct(Container $container, BeanRegisterer $beanRegisterer) {
        $this->container = $container;
        $this->beanRegisterer = $beanRegisterer;
    }
}
