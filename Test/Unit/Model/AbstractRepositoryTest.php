<?php

declare(strict_types=1);

namespace BetterMagento\Core\Test\Unit\Model;

use BetterMagento\Core\Model\AbstractRepository;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractRepositoryTest extends TestCase
{
    private AbstractDb&MockObject $resourceModel;
    private SearchResultsInterfaceFactory&MockObject $searchResultsFactory;
    private CollectionProcessorInterface&MockObject $collectionProcessor;
    private AbstractModel&MockObject $model;

    /** @var AbstractRepository<AbstractModel> */
    private AbstractRepository $repository;

    protected function setUp(): void
    {
        $this->resourceModel        = $this->createMock(AbstractDb::class);
        $this->searchResultsFactory = $this->createMock(SearchResultsInterfaceFactory::class);
        $this->collectionProcessor  = $this->createMock(CollectionProcessorInterface::class);
        $this->model                = $this->createMock(AbstractModel::class);

        $model = $this->model;

        // Build a concrete anonymous subclass
        $this->repository = new class(
            $this->resourceModel,
            $this->searchResultsFactory,
            $this->collectionProcessor,
            $model,
        ) extends AbstractRepository {
            public function __construct(
                AbstractDb $resourceModel,
                SearchResultsInterfaceFactory $searchResultsFactory,
                CollectionProcessorInterface $collectionProcessor,
                private readonly AbstractModel $modelPrototype,
            ) {
                parent::__construct($resourceModel, $searchResultsFactory, $collectionProcessor);
            }

            protected function createModel(): AbstractModel
            {
                return $this->modelPrototype;
            }

            protected function createCollection(): AbstractCollection
            {
                // Not needed for most tests — will be supplied in getList tests
                throw new \LogicException('Collection not configured in this test instance');
            }
        };
    }

    // ---- getById ------------------------------------------------------------

    public function testGetByIdLoadsAndReturnModel(): void
    {
        $this->model->method('getId')->willReturn(42);

        $this->resourceModel
            ->expects(self::once())
            ->method('load')
            ->with($this->model, 42);

        $result = $this->repository->getById(42);

        self::assertSame($this->model, $result);
    }

    public function testGetByIdReturnsCachedInstanceOnSecondCall(): void
    {
        $this->model->method('getId')->willReturn(1);

        $this->resourceModel
            ->expects(self::once())  // load called only once
            ->method('load');

        $this->repository->getById(1);
        $this->repository->getById(1);
    }

    public function testGetByIdThrowsNoSuchEntityWhenNotFound(): void
    {
        $this->model->method('getId')->willReturn(null);

        $this->expectException(NoSuchEntityException::class);

        $this->repository->getById(999);
    }

    // ---- save ---------------------------------------------------------------

    public function testSaveDelegatesToResourceModel(): void
    {
        $this->model->method('getId')->willReturn(5);

        $this->resourceModel
            ->expects(self::once())
            ->method('save')
            ->with($this->model);

        $result = $this->repository->save($this->model);

        self::assertSame($this->model, $result);
    }

    public function testSaveThrowsCouldNotSaveExceptionOnFailure(): void
    {
        $this->resourceModel
            ->method('save')
            ->willThrowException(new \Exception('DB error'));

        $this->expectException(CouldNotSaveException::class);

        $this->repository->save($this->model);
    }

    // ---- delete -------------------------------------------------------------

    public function testDeleteDelegatesToResourceModel(): void
    {
        $this->model->method('getId')->willReturn(3);

        $this->resourceModel
            ->expects(self::once())
            ->method('delete')
            ->with($this->model);

        $this->repository->delete($this->model);
    }

    public function testDeleteThrowsCouldNotDeleteExceptionOnFailure(): void
    {
        $this->model->method('getId')->willReturn(3);

        $this->resourceModel
            ->method('delete')
            ->willThrowException(new \Exception('Constraint violation'));

        $this->expectException(CouldNotDeleteException::class);

        $this->repository->delete($this->model);
    }

    public function testDeleteEvictsFromCache(): void
    {
        $this->model->method('getId')->willReturn(7);

        // Prime the cache
        $this->resourceModel->method('load')->willReturnCallback(function () {});
        $this->repository->getById(7);

        // Delete
        $this->repository->delete($this->model);

        // After delete, load should be called again (cache miss)
        $this->resourceModel
            ->expects(self::once())
            ->method('load');

        $this->repository->getById(7);
    }

    // ---- getList ------------------------------------------------------------

    public function testGetListAppliesCollectionProcessorAndReturnsResults(): void
    {
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $collection     = $this->createMock(AbstractCollection::class);
        $results        = $this->createMock(SearchResultsInterface::class);

        $collection->method('getItems')->willReturn([]);
        $collection->method('getSize')->willReturn(0);

        $this->collectionProcessor
            ->expects(self::once())
            ->method('process')
            ->with($searchCriteria, $collection);

        $this->searchResultsFactory
            ->method('create')
            ->willReturn($results);

        $results->expects(self::once())->method('setSearchCriteria')->with($searchCriteria);
        $results->expects(self::once())->method('setItems')->with([]);
        $results->expects(self::once())->method('setTotalCount')->with(0);

        // Build repository that returns a collection
        $repo = new class(
            $this->resourceModel,
            $this->searchResultsFactory,
            $this->collectionProcessor,
            $this->model,
            $collection,
        ) extends AbstractRepository {
            public function __construct(
                AbstractDb $resourceModel,
                SearchResultsInterfaceFactory $searchResultsFactory,
                CollectionProcessorInterface $collectionProcessor,
                private readonly AbstractModel $modelPrototype,
                private readonly AbstractCollection $collection,
            ) {
                parent::__construct($resourceModel, $searchResultsFactory, $collectionProcessor);
            }

            protected function createModel(): AbstractModel { return $this->modelPrototype; }
            protected function createCollection(): AbstractCollection { return $this->collection; }
        };

        $repo->getList($searchCriteria);
    }

    // ---- cache management ---------------------------------------------------

    public function testClearCacheForcesFreshLoad(): void
    {
        $this->model->method('getId')->willReturn(10);

        $this->resourceModel
            ->expects(self::exactly(2))
            ->method('load');

        $this->repository->getById(10);
        $this->repository->clearCache();
        $this->repository->getById(10);
    }

    public function testEvictRemovesSingleEntry(): void
    {
        $this->model->method('getId')->willReturn(20);

        $this->resourceModel
            ->expects(self::exactly(2))
            ->method('load');

        $this->repository->getById(20);
        $this->repository->evict(20);
        $this->repository->getById(20);
    }

    // ---- deleteById ---------------------------------------------------------

    public function testDeleteByIdLoadsThenDeletesModel(): void
    {
        $this->model->method('getId')->willReturn(30);

        $this->resourceModel
            ->expects(self::once())
            ->method('load')
            ->with($this->model, 30);

        $this->resourceModel
            ->expects(self::once())
            ->method('delete')
            ->with($this->model);

        $this->repository->deleteById(30);
    }
}
