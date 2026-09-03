<?php

declare(strict_types=1);

/**
 * Standalone unit-test bootstrap.
 *
 * Magento generates *Factory classes at runtime (generated/code) — they never
 * exist in the framework source, so standalone PHPUnit cannot mock them.
 * Inside a full Magento install the real generated classes take precedence
 * thanks to the class_exists() guards below.
 */

namespace {
    require __DIR__ . '/../../vendor/autoload.php';
}

namespace Magento\Framework\Api {
    if (!class_exists(SearchResultsInterfaceFactory::class)) {
        /**
         * Minimal stand-in for the generated factory. Only the signature
         * used by our code under test is declared; tests mock it anyway.
         */
        class SearchResultsInterfaceFactory
        {
            public function create(array $data = []): SearchResultsInterface
            {
                throw new \LogicException('Stub factory must be mocked in tests.');
            }
        }
    }
}
