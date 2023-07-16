<?php

namespace PhpBeans\Scanner;

use Laminas\Code\Reflection\ClassReflection;
use Metadata\MetadataFactory;
use PhpBeans\Annotation\IgnoreScanner;
use PhpBeans\Metadata\FileReflection;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Vox\Metadata\ClassMetadata;

class ComponentScanner
{
    private MetadataFactory $metadataFactory;

    private bool $debug;

    private ?CacheInterface $cache;
    
    public function __construct(MetadataFactory $metadataFactory, $debug = false, ?CacheInterface $cache = null) {
        $this->metadataFactory = $metadataFactory;
        $this->debug = $debug;
        $this->cache = $cache;
    }

    /**
     * @return ClassMetadata[]
     */
    public function scanComponentsFor(string $className, string ...$namespaces): array {
        $components = new \SplObjectStorage();
        $paths = [];

        foreach ($namespaces as $namespace) {
            /* @var $loader \Composer\Autoload\ClassLoader */
            $loader = require 'vendor/autoload.php';

            $paths = array_unique(array_merge($paths, $loader->getPrefixes()[$namespace] ?? [],
                                              $loader->getPrefixesPsr4()[$namespace] ?? []));
        }

        /* @var $class ClassReflection */
        foreach($this->getFiles($className, $paths) as $class) {
            $metadata = $this->metadataFactory->getMetadataForClass($class->getName());
            $metadata->fileResources[] = $class->getFileName();

            if (($metadata->hasAnnotation($className) || $this->implementsInterface($class, $className)
                || $class->isSubclassOf($className)) && !$metadata->hasAnnotation(IgnoreScanner::class)) {
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
        $files = $this->findFiles($className, $paths);

        foreach ($files as $file) {
            try {
                yield from (new FileReflection($file))->getClasses();
            } catch (\Throwable $e) {
                yield from [];
            }
        }
    }

    public function findFiles(string $className, array $paths) {
        $cacheKey = sprintf('scanner.%s-%s', str_replace('\\', '', $className), md5(implode('-', $paths)));

        if ($this->cache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        if (PHP_OS_FAMILY == 'Windows') {
            $files = $this->findFilesWindows($className, $paths);
        } else {
            $files = $this->findFilesLinux($className, $paths);
        }

        if ($this->cache) {
            $this->cache->set($cacheKey, $files);
        }

        return $files;
    }

    private function findFilesLinux(string $className, array $paths) {
        $command = trim(sprintf('grep -rP "(\@|\#\[|extends\s+|implements\s+)([^\S]+%1$s|%1$s)" %2$s',
                                $this->getShortClassName($className),
                                implode(" ", $paths)));

        $result = exec($command, $output, $exitCode);

        return array_filter(array_map(function ($line) {
            preg_match('/.*\.php/', $line, $matches);

            if (!$matches) {
                return null;
            }

            return trim($matches[0]);
        }, $output));
    }

    private function findFilesWindows(string $className, array $paths) {
        $files = iterator_to_array(
            (new Finder())->in($paths)
                ->contains(sprintf('/(\@|\#\[|extends\s+|implements\s+)([^\S]+%1$s|%1$s)/',
                           $this->getShortClassName($className)))
                ->getIterator()
        );

        return array_map(fn(SplFileInfo $file) => $file->getPathname(), $files);
    }

    private function getShortClassName(string $className): string {
        return basename(str_replace("\\", "/", $className));
    }

    private function implementsInterface(\ReflectionClass $class, string $interface) {
        try {
            return $class->implementsInterface($interface);
        } catch (\Exception $e) {
            return false;
        }
    }
}
