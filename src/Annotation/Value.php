<?php

namespace PhpBeans\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Value
{
    /**
     * @var string
     */
    public $beanId;
}
