<?php

namespace PhpBeans\Scanner;

use Laminas\Code\Reflection\FileReflection;
use Metadata\MetadataFactory;
use Symfony\Component\Finder\Finder;
use Vox\Metadata\ClassMetadata;

class ComponentScanner {
    private MetadataFactory $metadataFactory;
    
    public function __construct(MetadataFactory $metadataFactory) {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @param string $className
     * 
     * @return ClassMetadata[]
     */
    public function scanComponentsFor(string $className, string ...$namespaces): array {
        $components = new \SplObjectStorage();
        $paths = [];

        foreach ($namespaces as $namespace) {
            /* @var $loader \Composer\Autoload\ClassLoader */
            $loader = require 'vendor/autoload.php';

            $paths = array_merge($paths, $loader->getPrefixes()[$namespace] ?? [],
                                 $loader->getPrefixesPsr4()[$namespace] ?? []);
        }

        foreach($this->getFiles($className, $paths) as $class) {
            $metadata = $this->metadataFactory->getMetadataForClass($class->getName());

            if ($metadata->hasAnnotation($className) || $this->implementsInterface($class, $className)
                || $class->isSubclassOf($className)) {
                $components->attach($metadata);
            }

            /* @var $methodMetadata \Vox\Metadata\MethodMetadata */
            foreach ($metadata->methodMetadata as $methodMetadata) {
                if ($methodMetadata->hasAnnotation($className)) {
                    $components->attach($metadata);
                }
            }
        }

        return iterator_to_array($components);
    }

    private function getFiles(string $className, array $paths) {
        if (PHP_OS_FAMILY == 'Windows') {
            return $this->getFilesWindows($className, $paths);
        }

        return $this->getFilesLinux($className, $paths);
    }

    private function getFilesLinux(string $className, array $paths) {
        $output = [];
        $exitCode = 0;

        $command = sprintf('grep -rP "(\@|extends\s+|implements\s+)([^\S]+%1$s|%1$s)" %2$s',
            $this->getShortClassName($className),
            implode(" ", $paths));

        $result = exec($command, $output, $exitCode);

        if ($exitCode > 0) {
            throw new \RuntimeException("error to execute SO scanner: $command, $result, $exitCode");
        }

        foreach ($output as $line) {
            yield from $this->getClassesFromLine($line);
        }
    }

    private function getFilesWindows(string $className, array $paths) {
        $files = (new Finder())->in($paths)
            ->contains(sprintf('/(\@|extends\s+|implements\s+)([^\S]+%1$s|%1$s)/',
                       $this->getShortClassName($className)))
            ->getIterator();

        foreach ($files as $file) {
            yield from (new FileReflection($file, true))->getClasses();
        }
    }

    private function getClassesFromLine(string $line) {
        try {
            preg_match('/.*\.php/', $line, $matches);

            if (!$matches) {
                return [];
            }

            $file = trim($matches[0]);

            return (new FileReflection($file, true))->getClasses();
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    private function getShortClassName(string $className): string {
        return basename($className);
    }

    private function implementsInterface(\ReflectionClass $class, string $interface) {
        try {
            return $class->implementsInterface($interface);
        } catch (\Exception $e) {
            return false;
        }
    }
}
