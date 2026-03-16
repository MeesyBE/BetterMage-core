<?php

declare(strict_types=1);

namespace BetterMagento\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base command for all BetterMagento CLI commands.
 *
 * Provides:
 *  - Typed $input / $output properties available in subclasses via run()
 *  - Colored output helpers: success(), error(), info(), comment()
 *  - Progress bar factory
 *  - Execution timing via startTimer() / elapsedMs()
 *
 * Usage:
 *   class MyCommand extends AbstractBmCommand {
 *       protected static $defaultName = 'bettermagento:my:command';
 *       protected function configure(): void { ... }
 *       protected function handle(): int {
 *           $this->info('Starting...');
 *           return Command::SUCCESS;
 *       }
 *   }
 */
abstract class AbstractBmCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    private float $startTime = 0.0;

    /**
     * Subclasses implement this instead of execute().
     * Return one of the Symfony\Component\Console\Command\Command constants:
     *   Command::SUCCESS (0), Command::FAILURE (1), Command::INVALID (2)
     */
    abstract protected function handle(): int;

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->startTime = microtime(true);

        return $this->handle();
    }

    // -------------------------------------------------------------------------
    // Output helpers
    // -------------------------------------------------------------------------

    protected function success(string $message): void
    {
        $this->output->writeln("<info>✓ {$message}</info>");
    }

    protected function error(string $message): void
    {
        $this->output->writeln("<error>✗ {$message}</error>");
    }

    protected function info(string $message): void
    {
        $this->output->writeln("<comment>ℹ {$message}</comment>");
    }

    protected function comment(string $message): void
    {
        $this->output->writeln("  <fg=gray>{$message}</>");
    }

    protected function title(string $message): void
    {
        $line = str_repeat('─', max(4, strlen($message) + 4));
        $this->output->writeln([
            '',
            "<options=bold> {$message} </>",
            "<fg=gray>{$line}</>",
        ]);
    }

    // -------------------------------------------------------------------------
    // Progress bar
    // -------------------------------------------------------------------------

    protected function createProgressBar(int $max = 0): ProgressBar
    {
        $bar = new ProgressBar($this->output, $max);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        return $bar;
    }

    // -------------------------------------------------------------------------
    // Timing
    // -------------------------------------------------------------------------

    protected function elapsedMs(): int
    {
        return (int) round((microtime(true) - $this->startTime) * 1000);
    }

    protected function printElapsed(): void
    {
        $this->comment(sprintf('Completed in %d ms', $this->elapsedMs()));
    }
}
