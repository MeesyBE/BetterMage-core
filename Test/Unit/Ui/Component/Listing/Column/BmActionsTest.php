<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Ui\Component\Listing\Column;

use BetterMagento\Core\Ui\Component\Listing\Column\BmActions;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BmActionsTest extends TestCase
{
    private UrlInterface&MockObject $urlBuilder;
    private BmActions $column;

    protected function setUp(): void
    {
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $context          = $this->createMock(ContextInterface::class);
        $factory          = $this->createMock(UiComponentFactory::class);

        $context->method('getProcessor')->willReturn(
            $this->createMock(\Magento\Framework\View\Element\UiComponent\Processor::class)
        );

        $this->column = new BmActions(
            $context,
            $factory,
            $this->urlBuilder,
            [],
            [
                'name'   => 'actions',
                'config' => [
                    'viewUrlPath'   => 'mymodule/entity/view',
                    'editUrlPath'   => 'mymodule/entity/edit',
                    'deleteUrlPath' => 'mymodule/entity/delete',
                    'indexField'    => 'entity_id',
                ],
            ],
        );
    }

    public function testPrepareDataSourceAddsViewEditAndDeleteLinks(): void
    {
        $this->urlBuilder->method('getUrl')->willReturnCallback(
            static fn(string $path, array $params) => "http://shop.test/{$path}/" . implode('/', $params)
        );

        $dataSource = [
            'data' => [
                'items' => [
                    ['entity_id' => 42, 'name' => 'foo'],
                ],
            ],
        ];

        $result = $this->column->prepareDataSource($dataSource);
        $item   = $result['data']['items'][0];

        self::assertArrayHasKey('actions', $item);
        self::assertArrayHasKey('view', $item['actions']);
        self::assertArrayHasKey('edit', $item['actions']);
        self::assertArrayHasKey('delete', $item['actions']);
        self::assertStringContainsString('view', $item['actions']['view']['href']);
        self::assertStringContainsString('edit', $item['actions']['edit']['href']);
        self::assertStringContainsString('delete', $item['actions']['delete']['href']);
    }

    public function testPrepareDataSourceSkipsItemsWithoutIndexField(): void
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['name' => 'no-id-here'],
                ],
            ],
        ];

        $result = $this->column->prepareDataSource($dataSource);

        self::assertArrayNotHasKey('actions', $result['data']['items'][0]);
    }

    public function testPrepareDataSourceReturnsUnchangedWhenNoItems(): void
    {
        $dataSource = ['data' => []];

        $result = $this->column->prepareDataSource($dataSource);

        self::assertSame($dataSource, $result);
    }

    public function testDeleteActionHasPostFlag(): void
    {
        $this->urlBuilder->method('getUrl')->willReturn('http://shop.test/url');

        $dataSource = ['data' => ['items' => [['entity_id' => 1]]]];
        $result     = $this->column->prepareDataSource($dataSource);

        self::assertTrue($result['data']['items'][0]['actions']['delete']['post']);
    }

    public function testViewActionIsOptionalWhenNotConfigured(): void
    {
        // Reconfigure column without viewUrlPath
        $context          = $this->createMock(ContextInterface::class);
        $factory          = $this->createMock(UiComponentFactory::class);

        $context->method('getProcessor')->willReturn(
            $this->createMock(\Magento\Framework\View\Element\UiComponent\Processor::class)
        );

        $column = new BmActions(
            $context,
            $factory,
            $this->urlBuilder,
            [],
            [
                'name'   => 'actions',
                'config' => [
                    'editUrlPath'   => 'mymodule/entity/edit',
                    'deleteUrlPath' => 'mymodule/entity/delete',
                    'indexField'    => 'entity_id',
                    // viewUrlPath intentionally omitted
                ],
            ],
        );

        $this->urlBuilder->method('getUrl')->willReturn('http://shop.test/url');

        $dataSource = ['data' => ['items' => [['entity_id' => 1]]]];
        $result     = $column->prepareDataSource($dataSource);

        self::assertArrayNotHasKey('view', $result['data']['items'][0]['actions']);
        self::assertArrayHasKey('edit', $result['data']['items'][0]['actions']);
        self::assertArrayHasKey('delete', $result['data']['items'][0]['actions']);
    }

}
