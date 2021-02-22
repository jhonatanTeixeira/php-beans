<?php

namespace PhpBeans\Bean;

use PhpBeans\Annotation\Autowired;
use PhpBeans\Container\Container;
use PhpBeans\Container\ContainerException;
use Vox\Metadata\PropertyMetadata;
use PhpBeans\Annotation\PostBeanProcessor;

/**
 * @PostBeanProcessor
 */
class AutowirePostBeanProcessor extends AbstractPropertyPostBeanProcessor
{
    public function getAnnotationClass(): string {
        return Autowired::class;
    }

    public function process(PropertyMetadata $property, $bean, Container $container, $annotation) {
        $type = $property->type;

        $dependency = $annotation->beanId ?: $type;

        if (!$dependency) {
            throw new ContainerException("Autowired must have a type or a bean id configured");
        }

        $property->setValue($bean, $container->get($dependency));
    }
}
