<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Console\Command;

use BetterMagento\Core\Console\Command\AbstractBmCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests AbstractBmCommand's framework: output helpers, timing, and the
 * execute()-delegates-to-run() contract.
 *
 * We test through a minimal anonymous-class subclass to avoid testing an
 * abstract class directly.
 */
class AbstractBmCommandTest extends TestCase
{
    private BufferedOutput $output;
    private InputInterface&MockObject $input;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->input  = $this->createMock(InputInterface::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCommand(int $returnCode = Command::SUCCESS, ?callable $body = null): AbstractBmCommand
    {
        return new class($returnCode, $body) extends AbstractBmCommand {
            public function __construct(
                private readonly int $code,
                private readonly mixed $body,
            ) {
                parent::__construct('test:command');
            }

            protected function handle(): int
            {
                if ($this->body !== null) {
                    ($this->body)($this);
                }
                return $this->code;
            }

            public function expose(string $method, mixed ...$args): mixed
            {
                return $this->$method(...$args);
            }
        };
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function testExecuteReturnsDelegatedCode(): void
    {
        $cmd = $this->makeCommand(Command::SUCCESS);
        $result = $cmd->run($this->input, $this->output);

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testExecuteReturnsFailureCode(): void
    {
        $cmd = $this->makeCommand(Command::FAILURE);
        $result = $cmd->run($this->input, $this->output);

        self::assertSame(Command::FAILURE, $result);
    }

    public function testSuccessHelperWritesCheckmark(): void
    {
        $cmd = $this->makeCommand(Command::SUCCESS, function (AbstractBmCommand $c): void {
            $c->expose('success', 'It worked');
        });

        $cmd->run($this->input, $this->output);

        self::assertStringContainsString('It worked', $this->output->fetch());
    }

    public function testErrorHelperWritesCross(): void
    {
        $cmd = $this->makeCommand(Command::SUCCESS, function (AbstractBmCommand $c): void {
            $c->expose('error', 'Something broke');
        });

        $cmd->run($this->input, $this->output);

        self::assertStringContainsString('Something broke', $this->output->fetch());
    }

    public function testInfoHelperWritesMessage(): void
    {
        $cmd = $this->makeCommand(Command::SUCCESS, function (AbstractBmCommand $c): void {
            $c->expose('info', 'FYI message');
        });

        $cmd->run($this->input, $this->output);

        self::assertStringContainsString('FYI message', $this->output->fetch());
    }

    public function testCommentHelperWritesMessage(): void
    {
        $cmd = $this->makeCommand(Command::SUCCESS, function (AbstractBmCommand $c): void {
            $c->expose('comment', 'side note');
        });

        $cmd->run($this->input, $this->output);

        self::assertStringContainsString('side note', $this->output->fetch());
    }

    public function testTitleHelperWritesMessage(): void
    {
        $cmd = $this->makeCommand(Command::SUCCESS, function (AbstractBmCommand $c): void {
            $c->expose('title', 'My Section');
        });

        $cmd->run($this->input, $this->output);

        self::assertStringContainsString('My Section', $this->output->fetch());
    }

    public function testElapsedMsReturnsNonNegativeInteger(): void
    {
        $elapsed = null;

        $cmd = $this->makeCommand(Command::SUCCESS, function (AbstractBmCommand $c) use (&$elapsed): void {
            $elapsed = $c->expose('elapsedMs');
        });

        $cmd->run($this->input, $this->output);

        self::assertIsInt($elapsed);
        self::assertGreaterThanOrEqual(0, $elapsed);
    }

    public function testPrintElapsedWritesMilliseconds(): void
    {
        $cmd = $this->makeCommand(Command::SUCCESS, function (AbstractBmCommand $c): void {
            $c->expose('printElapsed');
        });

        $cmd->run($this->input, $this->output);

        self::assertMatchesRegularExpression('/\d+ ms/', $this->output->fetch());
    }
}
