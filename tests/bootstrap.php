<?php

/* @var $loader \Composer\Autoload\ClassLoader */
$loader = require 'vendor/autoload.php';
$loader->addPsr4('PhpBeansTest\\', __DIR__);
$loader->addPsr4('ScannedTest\\', __DIR__ . '/example/scanned');
$loader->addPsr4('StaticTest\\', __DIR__ . '/example/static');
$loader->addPsr4('Shared\\', __DIR__ . '/example/shared');

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);

if (strtolower(PHP_OS_FAMILY) === 'windows') {
    exec("rd /s /q build\cache");
} else {
    exec("rm -rf build/cache");
}