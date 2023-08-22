<?php

namespace ScannedTest\Implementor;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use PhpBeans\Bean\AbstractInterfaceImplementor;
use PhpBeans\Metadata\ClassMetadata;
use Shared\Annotation\GeneratedClass;
use Shared\Stub\BarComponent;
use Vox\Metadata\MethodMetadata;

class InterfaceGenerator extends AbstractInterfaceImplementor
{
    public function getBehaviorName(): string
    {
        return GeneratedClass::class;
    }

    public function implementMethodBody(MethodGenerator $methodGenerator, MethodMetadata $metadata,
                                        ClassMetadata $classMetadata): string
    {
        if ($metadata->name == 'processValue') {
            return 'return $value + $this->component->getValue();';
        }
    }

    protected function postProcess(ClassMetadata $classMetadata, ClassGenerator $classGenerator)
    {
        $classGenerator->addMethod(
            '__construct',
            [
                new ParameterGenerator('component', BarComponent::class)
            ],
            MethodGenerator::FLAG_PUBLIC,
            '$this->component = $component;'
        )->addProperty('component');
    }
}