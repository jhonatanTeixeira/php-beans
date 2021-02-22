<?php


namespace PhpBeansTest\Config;

use PhpBeans\Annotation\Bean;
use PhpBeans\Annotation\Configuration;
use PhpBeansTest\Annotation\TestImport;
use PhpBeansTest\Stub\BarComponent;
use PhpBeansTest\Stub\BeanComponent;

/**
 * @Configuration
 * @TestImport
 */
class BeanConfiguration
{
    /**
     * @Bean
     */
    public function beanComponent(BarComponent $fooComponent): BeanComponent
    {
        return new BeanComponent($fooComponent);
    }

    /**
     * @Bean("someBean")
     */
    public function someBean(BarComponent $fooComponent): BeanComponent
    {
        return new BeanComponent($fooComponent);
    }
}