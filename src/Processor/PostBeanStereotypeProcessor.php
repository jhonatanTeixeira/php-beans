<?php

namespace PhpBeans\Processor;

use Closure;
use PhpBeans\Annotation\PostBeanProcessor;
use ReflectionClass;
use ReflectionFunction;
use RuntimeException;

class PostBeanStereotypeProcessor extends AbstractStereotypeProcessor
{
    public function getStereotypeName(): string {
        return PostBeanProcessor::class;
    }

    public function process($stereotype) {
        if (!is_callable($stereotype)) {
            throw new RuntimeException("Post bean processors must be callable");
        }

        $callable = Closure::fromCallable($stereotype);
        $params = (new ReflectionFunction($callable))->getParameters();

        if (!isset($params[0])) {
            throw new RuntimeException("Post bean processors must have a parameter");
        }

        /* @var $class ReflectionClass */
        $class = $params[0]->getClass();
        $name = $params[0]->name;

        if ($class && $class->isInstance($this->getContainer())) {
            $bean = $this->getContainer();
        } elseif ($this->getContainer()->has($name)) {
            $bean = $this->getContainer()->get($name);
        } elseif ($class && $this->getContainer()->has($class->name)) {
            $bean = $this->getContainer()->get($class->name);
        } else {
            throw new RuntimeException("Post bean processors must refer to a existing bean: $name or $class");
        }

        $stereotype($bean);
    }

}
