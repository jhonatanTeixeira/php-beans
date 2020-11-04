<?php

/* @var $loader \Composer\Autoload\ClassLoader */
$loader = require 'vendor/autoload.php';
//$loader->set('Vox\Metadata\Test\\', __DIR__);

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);