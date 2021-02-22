<?php

namespace PhpBeansTest\Config;

use PhpBeans\Annotation\Value;
use PhpBeansTest\Stub\TestImportService;
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