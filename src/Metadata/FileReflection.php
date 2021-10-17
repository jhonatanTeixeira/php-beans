<?php

namespace PhpBeans\Metadata;

class FileReflection
{
    private $path;

    private $contents;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->contents = file_get_contents($path);
    }

    public function getNamespace() {
        $matches = [];
        preg_match('/namespace (?P<namespace>[^;]+)/', $this->contents, $matches);

        return trim($matches['namespace'] ?? '');
    }

    public function getClasses() {
        $matches = [];
        preg_match_all(
            '/(?<!\S)((class)|(interface))\s+(?P<class>\S+)[\s\S]+?(?={)/mi',
            $this->contents,
            $matches
        );

        $namespace = $this->getNamespace();

        require_once "{$this->path}";

        return array_map(fn($c) => new \ReflectionClass("$namespace\\$c"), $matches['class']);
    }
}
