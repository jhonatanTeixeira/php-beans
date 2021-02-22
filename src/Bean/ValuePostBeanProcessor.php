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
    use ValueProcessorTrait;

    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Logger::getLogger(__CLASS__);
    }

    public function getAnnotationClass(): string {
        return Value::class;
    }
}