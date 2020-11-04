<?php

namespace PhpBeans\Bean;

use PhpBeans\Annotation\Autowired;
use PhpBeans\Annotation\PostBeanProcessor;
use PhpBeans\Container\Container;
use PhpBeans\Container\ContainerException;

/**
 * @PostBeanProcessor
 */
class AutowirePostBeanProcessor 
{
    public function __invoke(Container $container) {
        foreach ($container as $id => $bean) {
            if (!$container->hasMetadata($id)) {
                continue;
            }

            $metadata = $container->getMetadata($id);

            foreach ($metadata->getAnnotatedProperties(Autowired::class) as $autowired) {
                $type = $autowired->type;
                /* @var $annotation Autowired */
                $annotation = $autowired->getAnnotation(Autowired::class);

                $dependency = $annotation->beanId ?: $type;

                if (!$dependency) {
                    throw new ContainerException("Autowired must have a type or a bean id configured");
                }

                $autowired->setValue($bean, $container->get($dependency));
            }
        }
    }
}
