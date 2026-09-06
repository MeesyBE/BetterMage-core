<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Console\Command;

use BetterMagento\Core\Console\Command\StatusCommand;
use Magento\Framework\Module\ModuleListInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class StatusCommandTest extends TestCase
{
    private ModuleListInterface&MockObject $moduleList;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->moduleList = $this->createMock(ModuleListInterface::class);
        $command          = new StatusCommand($this->moduleList);
        $this->tester     = new CommandTester($command);
    }

    /** @param array<string, array<string, mixed>> $modules */
    private function stubModules(array $modules): void
    {
        $this->moduleList->method('getAll')->willReturn($modules);
    }

    public function testTableFormatListsBmModulesAndSucceeds(): void
    {
        $this->stubModules([
            'Magento_Catalog'     => ['setup_version' => '2.4.0'],
            'BetterMagento_Core'  => ['setup_version' => '0.2.0', 'sequence' => []],
            'BetterMagento_Pulse' => ['setup_version' => '0.1.0', 'sequence' => ['BetterMagento_Core']],
        ]);

        $exitCode = $this->tester->execute(['--format' => 'table']);

        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $this->tester->getDisplay();
        self::assertStringContainsString('BetterMagento Module Status', $output);
        self::assertStringContainsString('BetterMagento_Core', $output);
        self::assertStringContainsString('BetterMagento_Pulse', $output);
        self::assertStringNotContainsString('Magento_Catalog', $output);
        self::assertStringContainsString('2 BetterMagento module(s) active', $output);
    }

    public function testCoreModuleRendersDashCoreDependency(): void
    {
        $this->stubModules([
            'BetterMagento_Core' => ['setup_version' => '0.2.0', 'sequence' => []],
        ]);

        $this->tester->execute([]);

        self::assertStringNotContainsString('Missing', $this->tester->getDisplay());
        self::assertStringContainsString('1 BetterMagento module(s) active', $this->tester->getDisplay());
    }

    public function testMissingCoreDependencyShowsWarning(): void
    {
        $this->stubModules([
            'BetterMagento_Legacy' => ['setup_version' => '0.1.0', 'sequence' => []],
        ]);

        $this->tester->execute([]);

        $output = $this->tester->getDisplay();
        self::assertStringContainsString('Missing', $output);
        self::assertStringContainsString('1 module(s) missing Core dependency', $output);
    }

    public function testJsonFormatOutputsValidJson(): void
    {
        $this->stubModules([
            'BetterMagento_Core'  => ['setup_version' => '0.2.0', 'sequence' => []],
            'BetterMagento_Pulse' => ['setup_version' => '0.1.0', 'sequence' => ['BetterMagento_Core']],
        ]);

        $this->tester->execute(['--format' => 'json']);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());

        // The title banner precedes the decoded JSON payload; parse from its start.
        $json = substr($this->tester->getDisplay(), strpos($this->tester->getDisplay(), '{'));
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(2, $decoded['count']);
        self::assertSame('BetterMagento_Core', $decoded['modules'][0]['module']);
        self::assertFalse($decoded['modules'][0]['has_core_dependency']);
        self::assertSame('BetterMagento_Pulse', $decoded['modules'][1]['module']);
        self::assertTrue($decoded['modules'][1]['has_core_dependency']);
    }

    public function testCsvFormatOutputsHeaderAndRows(): void
    {
        $this->stubModules([
            'BetterMagento_Core'  => ['setup_version' => '0.2.0', 'sequence' => []],
            'BetterMagento_Pulse' => ['setup_version' => '0.1.0', 'sequence' => ['BetterMagento_Core']],
        ]);

        $this->tester->execute(['--format' => 'csv']);

        $output = $this->tester->getDisplay();
        self::assertStringContainsString('Module,Version,Has Core Dependency', $output);
        self::assertStringContainsString('BetterMagento_Core,0.2.0,no', $output);
        self::assertStringContainsString('BetterMagento_Pulse,0.1.0,yes', $output);
    }

    public function testNoBmModulesReturnsFailure(): void
    {
        $this->stubModules(['Magento_Catalog' => ['setup_version' => '2.4.0']]);

        $this->tester->execute([]);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('No BetterMagento modules found', $this->tester->getDisplay());
    }

    public function testSchemaVersionUsedWhenSetupVersionMissing(): void
    {
        $this->stubModules([
            'BetterMagento_Core' => ['schema_version' => '0.3.0', 'sequence' => []],
        ]);

        $this->tester->execute(['--format' => 'csv']);

        self::assertStringContainsString('BetterMagento_Core,0.3.0,no', $this->tester->getDisplay());
    }
}