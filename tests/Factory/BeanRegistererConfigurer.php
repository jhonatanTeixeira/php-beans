<?php


namespace PhpBeansTest\Factory;


use PhpBeans\Bean\BeanRegisterer;
use PhpBeans\Bean\BeanRegistererConfiguratorInterface;

class BeanRegistererConfigurer implements BeanRegistererConfiguratorInterface
{
    public function configure(BeanRegisterer $beanRegisterer)
    {
        $beanRegisterer->addNamespace('PhpBeansTest\\')
            ->addComponent(SomeRegisteredTestComponent::class)
            ->addBehavior(SomeTestBehavior::class)
        ;
    }
}

class SomeRegisteredTestComponent {
    public function getName() {
        return 'test component';
    }
}