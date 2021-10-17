<?php

namespace PhpBeans\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Component 
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
