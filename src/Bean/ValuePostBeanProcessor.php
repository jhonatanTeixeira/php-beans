<?php


namespace PhpBeans\Bean;

use PhpBeans\Annotation\Value;
use PhpBeans\Container\Container;
use Psr\Log\LoggerInterface;
use Vox\Log\Logger;
use Vox\Metadata\PropertyMetadata;
use PhpBeans\Annotation\PostBeanProcessor;

/**
 * @PostBeanProcessor
 */
class ValuePostBeanProcessor extends AbstractPropertyPostBeanProcessor
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Logger::getLogger(__CLASS__);
    }


    public function getAnnotationClass(): string {
        return Value::class;
    }

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
        }
    }
}