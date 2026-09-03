<?php

declare(strict_types=1);

namespace BetterMagento\Core\Model;

use BetterMagento\Core\Api\RepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Generic CRUD repository base for all BetterMagento data models.
 *
 * Subclass and inject the concrete ResourceModel, Model factory, and
 * Collection factory. The in-memory identity map prevents duplicate database
 * reads within a single request.
 *
 * Usage (in a concrete repository):
 *
 *   class MyEntityRepository extends AbstractRepository
 *   {
 *       public function __construct(
 *           private readonly MyEntityFactory $modelFactory,
 *           AbstractDb $resourceModel,
 *           SearchResultsInterfaceFactory $searchResultsFactory,
 *           CollectionProcessorInterface $collectionProcessor,
 *           private readonly MyEntityCollectionFactory $collectionFactory,
 *       ) {
 *           parent::__construct($resourceModel, $searchResultsFactory, $collectionProcessor);
 *       }
 *
 *       protected function createModel(): AbstractModel
 *       {
 *           return $this->modelFactory->create();
 *       }
 *
 *       protected function createCollection(): AbstractCollection
 *       {
 *           return $this->collectionFactory->create();
 *       }
 *   }
 *
 * @template TModel of AbstractModel
 * @template TId of int|string
 * @implements RepositoryInterface<TModel, TId>
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * In-memory identity map: primary-key → model instance.
     * Cleared automatically after save/delete to keep it consistent.
     *
     * @var array<int|string, AbstractModel>
     */
    private array $cache = [];

    public function __construct(
        private readonly AbstractDb $resourceModel,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
    ) {}

    // -------------------------------------------------------------------------
    // Abstract factory methods — subclasses provide concrete types
    // -------------------------------------------------------------------------

    /**
     * Create a fresh (unpopulated) model instance.
     */
    abstract protected function createModel(): AbstractModel;

    /**
     * Create a fresh (unpopulated) collection instance.
     */
    abstract protected function createCollection(): AbstractCollection;

    // -------------------------------------------------------------------------
    // RepositoryInterface implementation
    // -------------------------------------------------------------------------

    public function getById(int|string $id): AbstractModel
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $model = $this->createModel();
        $this->resourceModel->load($model, $id);

        if (!$model->getId()) {
            throw NoSuchEntityException::singleField('id', $id);
        }

        $this->cache[$id] = $model;
        return $model;
    }

    public function save(AbstractModel $model): AbstractModel
    {
        try {
            $this->resourceModel->save($model);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save: %1', $e->getMessage()),
                $e,
            );
        }

        // Refresh cache entry after save (ID may have changed for new records)
        if ($model->getId()) {
            $this->cache[$model->getId()] = $model;
        }

        return $model;
    }

    public function delete(AbstractModel $model): void
    {
        try {
            $id = $model->getId();
            $this->resourceModel->delete($model);
            unset($this->cache[$id]);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete: %1', $e->getMessage()),
                $e,
            );
        }
    }

    public function deleteById(int|string $id): void
    {
        $this->delete($this->getById($id));
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->createCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $items = array_values(array_filter(
            $collection->getItems(),
            static fn($item) => $item instanceof ExtensibleDataInterface
        ));
        $results->setItems($items);
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    // -------------------------------------------------------------------------
    // Cache management (useful for testing and long-running processes)
    // -------------------------------------------------------------------------

    /**
     * Remove a specific entry from the in-memory cache.
     */
    public function evict(int|string $id): void
    {
        unset($this->cache[$id]);
    }

    /**
     * Clear the entire in-memory identity map.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
