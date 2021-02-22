<?php


namespace PhpBeans\Bean;


use PhpBeans\Container\Container;
use PhpBeans\Metadata\ClassMetadata;
use Vox\Metadata\PropertyMetadata;

abstract class AbstractPropertyPostBeanProcessor
{
    public function __invoke(Container $container)
    {
        foreach ($container as $id => $bean) {
            if (!$container->hasMetadata($id)) {
                continue;
            }

            $metadata = $container->getMetadata($id);

            foreach ($metadata->getAnnotatedProperties($this->getAnnotationClass()) as $property) {
                $annotation = $property->getAnnotation($this->getAnnotationClass());

                $this->process($property, $bean, $container, $annotation);
            }
        }
    }

    public abstract function getAnnotationClass(): string;

    public abstract function process(PropertyMetadata $property, $bean, Container $container, $annotation);
}