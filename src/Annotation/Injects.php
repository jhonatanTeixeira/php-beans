<?php

namespace PhpBeans\Annotation;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Injects
{
    public string $beanId;

    public function __construct(string $beanId)
    {
        $this->beanId = $beanId;
    }
}
