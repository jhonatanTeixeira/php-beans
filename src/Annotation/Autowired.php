<?php

namespace PhpBeans\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Autowired 
{
    /**
     * @var string
     */
    public $beanId;
}
