<?php

declare(strict_types=1);

namespace BetterMagento\Core\Console\Command;

use Magento\Framework\Module\ModuleListInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento bettermagento:status
 *
 * Prints a table of all BetterMagento_* modules that are currently enabled,
 * their sequence number (setup_version), and whether they declare a dependency
 * on BetterMagento_Core.
 *
 * Example output:
 *   BetterMagento Status
 *   ────────────────────
 *   ┌──────────────────────────────┬─────────┬──────────┐
 *   │ Module                       │ Version │ Core Dep │
 *   ├──────────────────────────────┼─────────┼──────────┤
 *   │ BetterMagento_Core           │ 0.1.0   │ —        │
 *   │ BetterMagento_TurboCore      │ 0.1.0   │ ✓        │
 *   └──────────────────────────────┴─────────┴──────────┘
 *   ✓ 2 BetterMagento module(s) active
 */
class StatusCommand extends AbstractBmCommand
{
    protected static $defaultName = 'bettermagento:status';

    private const BM_PREFIX = 'BetterMagento_';

    public function __construct(
        private readonly ModuleListInterface $moduleList,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Show status of all installed BetterMagento modules.')
            ->setName(self::$defaultName)
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format: table (default), json, or csv',
                'table',
            );
    }

    protected function handle(): int
    {
        $this->title('BetterMagento Module Status');

        $allModules = $this->moduleList->getAll();

        $bmModules = array_filter(
            $allModules,
            static fn(string $name) => str_starts_with($name, self::BM_PREFIX),
            ARRAY_FILTER_USE_KEY,
        );

        if (empty($bmModules)) {
            $this->error('No BetterMagento modules found.');
            return Command::FAILURE;
        }

        ksort($bmModules);

        $format = $this->input->getOption('format') ?? 'table';

        match ($format) {
            'json' => $this->outputJson($bmModules),
            'csv' => $this->outputCsv($bmModules),
            default => $this->outputTable($bmModules),
        };

        return Command::SUCCESS;
    }

    /**
     * Render modules as an ASCII table with color-coded indicators.
     *
     * @param array<string, array<string, mixed>> $bmModules
     */
    private function outputTable(array $bmModules): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Module', 'Version', 'Core Dependency']);

        $depIssues = [];

        foreach ($bmModules as $name => $info) {
            $version = (string) ($info['setup_version'] ?? $info['schema_version'] ?? '—');

            if ($name === 'BetterMagento_Core') {
                $coreDep = '—';
            } else {
                $hasDep = $this->hasCoreSequence($info);
                $coreDep = $hasDep ? '<fg=green>✓</> Core 0.3.0+' : '<comment>✗ Missing</comment>';
                if (!$hasDep) {
                    $depIssues[] = $name;
                }
            }

            $table->addRow([$name, $version, $coreDep]);
        }

        $table->render();

        // Summary
        $this->output->writeln('');
        $count = count($bmModules);
        $this->success(sprintf('<info>✓</info> <fg=green>%d BetterMagento module(s) active</>', $count));

        if (!empty($depIssues)) {
            $this->output->writeln('');
            $this->error(sprintf(
                '<fg=red>!</> <comment>%d module(s) missing Core dependency in module.xml</comment>',
                count($depIssues),
            ));
            $this->output->writeln('  Fix: Add <fg=cyan><sequence><module name="BetterMagento_Core"/></sequence></> to module.xml');
        }

        $this->printElapsed();
    }

    /**
     * Render modules as JSON for scripting.
     *
     * @param array<string, array<string, mixed>> $bmModules
     */
    private function outputJson(array $bmModules): void
    {
        $data = [];
        foreach ($bmModules as $name => $info) {
            $version = (string) ($info['setup_version'] ?? $info['schema_version'] ?? '');
            $hasDep = ($name !== 'BetterMagento_Core') && $this->hasCoreSequence($info);

            $data[] = [
                'module' => $name,
                'version' => $version,
                'has_core_dependency' => $hasDep,
            ];
        }

        $this->output->writeln(json_encode(
            ['modules' => $data, 'count' => count($data)],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));
    }

    /**
     * Render modules as CSV for import/analysis.
     *
     * @param array<string, array<string, mixed>> $bmModules
     */
    private function outputCsv(array $bmModules): void
    {
        // Header
        $this->output->writeln('Module,Version,Has Core Dependency');

        foreach ($bmModules as $name => $info) {
            $version = (string) ($info['setup_version'] ?? $info['schema_version'] ?? '');
            $hasDep = ($name !== 'BetterMagento_Core') && $this->hasCoreSequence($info);

            $this->output->writeln(sprintf(
                '%s,%s,%s',
                $name,
                $version,
                $hasDep ? 'yes' : 'no',
            ));
        }
    }

    /**
     * Check whether this module's module.xml sequence includes BetterMagento_Core.
     * ModuleListInterface exposes the full module info array from module.xml.
     *
     * @param array<string, mixed> $info
     */
    private function hasCoreSequence(array $info): bool
    {
        $sequence = (array) ($info['sequence'] ?? []);
        return in_array('BetterMagento_Core', $sequence, true);
    }
}
