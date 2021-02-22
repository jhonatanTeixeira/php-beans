<?php

/* @var $loader \Composer\Autoload\ClassLoader */
$loader = require 'vendor/autoload.php';
//$loader->set('Vox\Metadata\Test\\', __DIR__);

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);

if (PHP_OS === 'Windows') {
    exec("rd /s /q build/cache");
} else {
    exec("rm -rf build/cache");
}