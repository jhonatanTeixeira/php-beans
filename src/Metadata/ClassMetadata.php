<?php

namespace PhpBeans\Metadata;

use ReflectionClass;
use ReflectionParameter;
use Vox\Metadata\ClassMetadata as BaseMetadata;
use Vox\Metadata\MethodMetadata;
use Vox\Metadata\PropertyMetadata;

class ClassMetadata extends BaseMetadata
{
    use ParamResolverTrait;

    public function getConstructorParams() {
        $constructor = $this->getConstructor();
        
        if (!$constructor) {
            return [];
        }

        return $constructor->params;
    }
    
    public function getReflection(): ReflectionClass {
        return $this->reflection;
    }
    
    /**
     * @param string $annotation
     * 
     * @return MethodMetadata[]
     */
    public function getAnnotatedMethods(string $annotation) {
        return array_filter(
            $this->methodMetadata,
            fn(MethodMetadata $metadata) => $metadata->hasAnnotation($annotation)
        );
    }

    /**
     * @param string $annotation
     * 
     * @return PropertyMetadata[]
     */
    public function getAnnotatedProperties(string $annotation) {
        return array_filter(
            $this->propertyMetadata,
            fn(PropertyMetadata $metadata) => $metadata->hasAnnotation($annotation)
        );
    }

    public function isInstanceOf(string $class) {
        return $this->implementsInterface($class)
            || $this->reflection->isSubclassOf($class);
    }

    public function implementsInterface(string $interface) {
        try {
            return $this->reflection->implementsInterface($interface);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getConstructor(): ?MethodMetadata {
        return $this->methodMetadata['__construct'] ?? null;
    }
}
