<?php

namespace PhpBeans\Bean;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Reflection\MethodReflection;
use Metadata\MetadataFactory;
use PhpBeans\Container\ContainerAwareInterface;
use PhpBeans\Container\ContainerAwareTrait;
use PhpBeans\Metadata\ClassMetadata;
use Vox\Metadata\MethodMetadata;

abstract class AbstractInterfaceImplementor implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public ?string $cacheDir = 'build/generated';

    abstract public function getBehaviorName(): string;

    abstract public function implementMethodBody(MethodGenerator $methodGenerator, MethodMetadata $metadata,
                                                 ClassMetadata $classMetadata): string;

    protected function getBlacklistedMethods(): array
    {
        return [];
    }

    public function createClassGenerator(ClassMetadata $metadata): ClassGenerator
    {
        $methods = [];

        foreach ($metadata->methodMetadata as $methodMetadata) {
            if (!$methodMetadata->reflection->isAbstract()
                || $methodMetadata->reflection->isProtected()
                || $methodMetadata->reflection->isPrivate()
                || in_array($methodMetadata->name, $this->getBlacklistedMethods())) {
                continue;
            }

            $methods[] = $method = MethodGenerator::copyMethodSignature(
                new MethodReflection($methodMetadata->class, $methodMetadata->name)
            );

            $method->setAbstract(false)
                ->setInterface(false);

            $method->setBody($this->implementMethodBody($method, $methodMetadata, $metadata));
        }

        $parentClass = $metadata->getReflection()->getParentClass();

        return new ClassGenerator(
            $metadata->name . 'Impl',
            $metadata->getReflection()->getNamespaceName(),
            null,
            $parentClass ? $parentClass->name : null,
            $metadata->getReflection()->isInterface() ? [$metadata->name] : [],
            [],
            $methods,
        );
    }

    protected function postProcess(ClassMetadata $classMetadata, ClassGenerator $classGenerator)
    {
        // override this method to do desired post-processing like, adding properties, constructors or autowired stuff
    }

    public function implementsClass(ClassMetadata $classMetadata)
    {
        $className = "{$classMetadata->name}Impl";
        $filename = "{$this->cacheDir}/" . str_replace('\\', '_', $className) . ".php";

        if (!class_exists($className) && file_exists($filename) && $classMetadata->isFresh()) {
            require_once $filename;
        } elseif (!class_exists($className) && !file_exists($filename) || !$classMetadata->isFresh()) {
            $generator = $this->createClassGenerator($classMetadata);
            $this->postProcess($classMetadata, $generator);

            if ($this->cacheDir) {
                if (!is_dir($this->cacheDir)) {
                    mkdir($this->cacheDir, 0755, true);
                }

                file_put_contents($filename, "<?php \n" . $generator->generate());

                require_once $filename;
            } else {
                eval($generator->generate());
            }
        }

        $metadata = $this->getContainer()
            ->get(MetadataFactory::class)
            ->getMetadataForClass($className);

        $this->getContainer()->set($classMetadata->name, $metadata);
    }

    public function accept(ClassMetadata $classMetadata)
    {
        return $classMetadata->hasAnnotation($this->getBehaviorName())
            || $classMetadata->isInstanceOf($this->getBehaviorName())
            || $classMetadata->implementsInterface($this->getBehaviorName());
    }
}