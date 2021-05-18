<?php

/* @var $loader \Composer\Autoload\ClassLoader */
$loader = require 'vendor/autoload.php';
//$loader->set('Vox\Metadata\Test\\', __DIR__);

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);

if (strtolower(PHP_OS) === 'windows' || strtolower(PHP_OS) === 'winnt') {
    exec("rd /s /q build\cache");
} else {
    exec("rm -rf build/cache");
}