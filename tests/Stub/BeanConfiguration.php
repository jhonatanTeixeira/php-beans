<?php


namespace PhpBeansTest\Stub;

use PhpBeans\Annotation\Bean;
use PhpBeans\Annotation\Configuration;

/**
 * @Configuration
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