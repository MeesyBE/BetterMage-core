<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Model\Config\Source;

use BetterMagento\Core\Model\Config\Source\LogLevel;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

class LogLevelTest extends TestCase
{
    public function testToOptionArrayReturnsFourLevels(): void
    {
        $options = (new LogLevel())->toOptionArray();

        self::assertCount(4, $options);
    }

    public function testOptionValuesMatchPsrLogLevels(): void
    {
        $options = (new LogLevel())->toOptionArray();

        $values = array_column($options, 'value');

        self::assertSame(['debug', 'info', 'warning', 'error'], $values);
    }

    public function testLabelsAreLocalizedPhrases(): void
    {
        $options = (new LogLevel())->toOptionArray();
        $labels  = array_column($options, 'label');

        foreach ($labels as $label) {
            self::assertInstanceOf(Phrase::class, $label);
        }
    }

    public function testEveryOptionHasValueAndLabel(): void
    {
        foreach ((new LogLevel())->toOptionArray() as $option) {
            self::assertArrayHasKey('value', $option);
            self::assertArrayHasKey('label', $option);
            self::assertNotSame('', $option['value']);
        }
    }
}