<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Model\Data;

use BetterMagento\Core\Model\Data\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testDefaultsToSuccessWithEmptyMessageAndData(): void
    {
        $result = new Result();

        self::assertTrue($result->isSuccess());
        self::assertSame('', $result->getMessage());
        self::assertSame([], $result->getData());
    }

    public function testWithSuccessReturnsClonesPreservingOriginal(): void
    {
        $original = new Result();
        $failed   = $original->withSuccess(false);

        self::assertTrue($original->isSuccess(), 'Original must not be mutated');
        self::assertFalse($failed->isSuccess());
        self::assertNotSame($original, $failed);
    }

    public function testWithMessageReturnsClonesPreservingOriginal(): void
    {
        $original = new Result();
        $withMsg  = $original->withMessage('Something failed');

        self::assertSame('', $original->getMessage(), 'Original must not be mutated');
        self::assertSame('Something failed', $withMsg->getMessage());
    }

    public function testWithDataReturnsClonesPreservingOriginal(): void
    {
        $original  = new Result();
        $withData  = $original->withData(['code' => 42]);

        self::assertSame([], $original->getData(), 'Original must not be mutated');
        self::assertSame(['code' => 42], $withData->getData());
    }

    public function testChainingBuildsCorrectResult(): void
    {
        $result = (new Result())
            ->withSuccess(false)
            ->withMessage('Cache backend unreachable')
            ->withData(['backend' => 'redis', 'host' => '127.0.0.1']);

        self::assertFalse($result->isSuccess());
        self::assertSame('Cache backend unreachable', $result->getMessage());
        self::assertSame(['backend' => 'redis', 'host' => '127.0.0.1'], $result->getData());
    }

    public function testWithSuccessTrueExplicit(): void
    {
        $result = (new Result())->withSuccess(false)->withSuccess(true);

        self::assertTrue($result->isSuccess());
    }

    public function testEmptyDataIsAllowed(): void
    {
        $result = (new Result())->withData([]);

        self::assertSame([], $result->getData());
    }
}
