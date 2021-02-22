<?php


namespace PhpBeans\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Imports
{
    /**
     * @var array<string>
     * @required
     */
    public $configurations;
}