<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Block\Adminhtml;

use BetterMagento\Core\Block\Adminhtml\BmModuleInfo;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BmModuleInfoTest extends TestCase
{
    private ModuleListInterface&MockObject $moduleList;
    private PackageInfo&MockObject $packageInfo;
    private Context&MockObject $context;
    private BmModuleInfo $block;

    protected function setUp(): void
    {
        $this->moduleList  = $this->createMock(ModuleListInterface::class);
        $this->packageInfo = $this->createMock(PackageInfo::class);
        $this->context     = $this->createMock(Context::class);

        $this->block = new BmModuleInfo(
            $this->context,
            $this->moduleList,
            $this->packageInfo,
            [],
            $this->createMock(JsonHelper::class),
            $this->createMock(DirectoryHelper::class),
        );
    }

    private function setupModules(array $modules): void
    {
        $this->moduleList->method('getAll')->willReturn($modules);
    }

    public function testGetBmModulesFiltersNonBmModules(): void
    {
        $this->setupModules([
            'Magento_Catalog'      => ['setup_version' => '2.4.0'],
            'BetterMagento_Core'   => ['setup_version' => '0.2.0', 'sequence' => []],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('');

        $rows = $this->block->getBmModules();

        self::assertCount(1, $rows);
        self::assertSame('BetterMagento_Core', $rows[0]['name']);
    }

    public function testGetBmModulesReturnsAlphabeticallySorted(): void
    {
        $this->setupModules([
            'BetterMagento_TurboCore' => ['setup_version' => '0.1.0', 'sequence' => ['BetterMagento_Core']],
            'BetterMagento_Core'      => ['setup_version' => '0.2.0', 'sequence' => []],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('');

        $rows = $this->block->getBmModules();

        self::assertSame('BetterMagento_Core', $rows[0]['name']);
        self::assertSame('BetterMagento_TurboCore', $rows[1]['name']);
    }

    public function testHasCoreDepTrueWhenSequenceContainsCore(): void
    {
        $this->setupModules([
            'BetterMagento_TurboCore' => [
                'setup_version' => '0.1.0',
                'sequence'      => ['BetterMagento_Core'],
            ],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('');

        $rows = $this->block->getBmModules();

        self::assertTrue($rows[0]['has_core_dep']);
        self::assertFalse($rows[0]['is_core']);
    }

    public function testHasCoreDepFalseWhenSequenceEmpty(): void
    {
        $this->setupModules([
            'BetterMagento_TurboCore' => ['setup_version' => '0.1.0', 'sequence' => []],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('');

        $rows = $this->block->getBmModules();

        self::assertFalse($rows[0]['has_core_dep']);
    }

    public function testCoreModuleIsFlaggedAsCore(): void
    {
        $this->setupModules([
            'BetterMagento_Core' => ['setup_version' => '0.2.0', 'sequence' => []],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('');

        $rows = $this->block->getBmModules();

        self::assertTrue($rows[0]['is_core']);
    }

    public function testComposerVersionTakesPrecedenceOverSetupVersion(): void
    {
        $this->setupModules([
            'BetterMagento_Core' => ['setup_version' => '0.1.0', 'sequence' => []],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('0.2.1');

        $rows = $this->block->getBmModules();

        self::assertSame('0.2.1', $rows[0]['version']);
    }

    public function testFallsBackToSetupVersionWhenComposerVersionEmpty(): void
    {
        $this->setupModules([
            'BetterMagento_Core' => ['setup_version' => '0.2.0', 'sequence' => []],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('');

        $rows = $this->block->getBmModules();

        self::assertSame('0.2.0', $rows[0]['version']);
    }

    public function testGetModuleCountMatchesBmModuleCount(): void
    {
        $this->setupModules([
            'Magento_Catalog'         => ['setup_version' => '2.4.0'],
            'BetterMagento_Core'      => ['setup_version' => '0.2.0', 'sequence' => []],
            'BetterMagento_TurboCore' => ['setup_version' => '0.1.0', 'sequence' => []],
        ]);
        $this->packageInfo->method('getVersion')->willReturn('');

        self::assertSame(2, $this->block->getModuleCount());
    }

    public function testEmptyModuleListReturnsEmptyArray(): void
    {
        $this->setupModules([]);

        self::assertSame([], $this->block->getBmModules());
        self::assertSame(0, $this->block->getModuleCount());
    }
}
