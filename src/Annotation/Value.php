<?php

namespace PhpBeans\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Value
{
    /**
     * @var string
     */
    public $beanId;

    public function __construct(string $beanId = null)
    {
        $this->beanId = $beanId;
    }
}
