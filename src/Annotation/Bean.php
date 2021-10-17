<?php

namespace PhpBeans\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Bean 
{
    /**
     * @var string
     */
    public $name;

    public function __construct(string $name = null)
    {
        $this->name = $name;
    }
}
