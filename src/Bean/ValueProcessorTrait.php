<?php


namespace PhpBeans\Bean;


use PhpBeans\Container\Container;
use Vox\Metadata\PropertyMetadata;

trait ValueProcessorTrait
{
    public function process(PropertyMetadata $property, $bean, Container $container, $annotation)
    {
        $valueId = $annotation->beanId;

        try {
            $value = $container->get($valueId);

            if (!is_scalar($value)) {
                throw new \InvalidArgumentException("bean with id {$valueId} is not a scalar value to be used with @Value");
            }

            $property->setValue($bean, $value);
        } catch (\Throwable $e) {
            $this->logger->debug("cannot process value {$valueId}: {$e->getMessage()}");
            $property->setValue($bean, $annotation->defaultValue);
        }
    }
}