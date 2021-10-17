<?php

namespace PhpBeans\Annotation;

use Attribute;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Autowired 
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
