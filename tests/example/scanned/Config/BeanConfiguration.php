<?php


namespace ScannedTest\Config;

use PhpBeans\Annotation\Bean;
use PhpBeans\Annotation\Configuration;
use Shared\Annotation\TestImport;
use Shared\Stub\BarComponent;
use Shared\Stub\BeanComponent;

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