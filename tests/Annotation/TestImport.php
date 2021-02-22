<?php


namespace PhpBeansTest\Annotation;

use PhpBeansTest\Config\TestImportConfig;
use PhpBeans\Annotation\Imports;


/**
 * @Annotation
 * @Imports({TestImportConfig::class})
 * @Target({"CLASS"})
 */
class TestImport
{

}