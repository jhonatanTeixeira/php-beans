<?php

namespace PhpBeans\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

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
     * @Required()
     */
    public $beanId;

    /**
     * @var string
     */
    public $defaultValue;

    public function __construct(string $beanId, string $defaultValue = null)
    {
        $this->beanId = $beanId;
        $this->defaultValue = $defaultValue;
    }
}
