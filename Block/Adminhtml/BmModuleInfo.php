<?php

declare(strict_types=1);

namespace BetterMagento\Core\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;

/**
 * Admin dashboard widget: BetterMagento module status.
 *
 * Renders a compact table of all enabled BetterMagento_* modules with their
 * installed composer version and a Core-dependency indicator.
 *
 * Template: view/adminhtml/templates/module-info.phtml
 *
 * Add to the Magento admin dashboard via layout XML:
 *   <block class="BetterMagento\Core\Block\Adminhtml\BmModuleInfo"
 *          name="bettermagento.module.info"
 *          template="BetterMagento_Core::module-info.phtml"/>
 */
class BmModuleInfo extends Template
{
    private const BM_PREFIX = 'BetterMagento_';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly ModuleListInterface $moduleList,
        private readonly PackageInfo $packageInfo,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null,
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Returns an array of BM module info rows for the template.
     *
     * Each row:
     *   ['name' => string, 'version' => string, 'has_core_dep' => bool]
     *
     * @return list<array{name: int|string, version: string, has_core_dep: bool, is_core: bool}>
     */
    public function getBmModules(): array
    {
        $all = $this->moduleList->getAll();
        $rows = [];

        foreach ($all as $name => $info) {
            if (!str_starts_with($name, self::BM_PREFIX)) {
                continue;
            }

            $composerVersion = $this->packageInfo->getVersion($name);
            $version = $composerVersion ?: ($info['setup_version'] ?? $info['schema_version'] ?? '—');

            $rows[] = [
                'name'         => $name,
                'version'      => (string) $version,
                'has_core_dep' => $this->hasCoreSequence($info),
                'is_core'      => ($name === self::BM_PREFIX . 'Core'),
            ];
        }

        usort($rows, static fn(array $a, array $b) => strcmp($a['name'], $b['name']));

        return $rows;
    }

    public function getModuleCount(): int
    {
        return count($this->getBmModules());
    }

    /** @param array<string, mixed> $info */
    private function hasCoreSequence(array $info): bool
    {
        return in_array('BetterMagento_Core', (array) ($info['sequence'] ?? []), true);
    }
}
