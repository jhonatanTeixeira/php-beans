<?php

namespace ScannedTest\Config;

use PhpBeans\Annotation\Value;
use Shared\Stub\TestImportService;
use PhpBeans\Annotation\Bean;

class TestImportConfig
{
    /**
     * @Value("someValue")
     */
    private $value;

    /**
     * @Bean
     */
    public function importService(): TestImportService {
        return new TestImportService($this->value);
    }
}