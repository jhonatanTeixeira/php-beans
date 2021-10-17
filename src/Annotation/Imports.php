<?php


namespace PhpBeans\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Imports
{
    /**
     * @var array<string>
     * @required
     */
    public $configurations;

    public function __construct(array $configurations)
    {
        $this->configurations = $configurations;
    }
}
