<?php

declare(strict_types=1);

namespace BetterMagento\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogLevel implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'debug',   'label' => __('Debug')],
            ['value' => 'info',    'label' => __('Info')],
            ['value' => 'warning', 'label' => __('Warning')],
            ['value' => 'error',   'label' => __('Error')],
        ];
    }
}
