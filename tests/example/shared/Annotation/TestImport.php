<?php


namespace Shared\Annotation;

use ScannedTest\Config\TestImportConfig;
use PhpBeans\Annotation\Imports;


/**
 * @Annotation
 * @Imports({TestImportConfig::class})
 * @Target({"CLASS"})
 */
class TestImport
{

}