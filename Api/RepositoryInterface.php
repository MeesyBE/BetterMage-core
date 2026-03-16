<?php

declare(strict_types=1);

namespace BetterMagento\Core\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * Generic CRUD repository contract for BetterMagento data models.
 *
 * Type parameters are expressed in PHPDoc only — PHP does not support generics.
 *
 * @template TModel of \Magento\Framework\Model\AbstractModel
 * @template TId    of int|string
 */
interface RepositoryInterface
{
    /**
     * Retrieve a model by its primary key.
     * Returns cached instance on repeated calls with the same $id.
     *
     * @param int|string $id
     * @return \Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\NoSuchEntityException when not found
     */
    public function getById(int|string $id): \Magento\Framework\Model\AbstractModel;

    /**
     * Persist a new or existing model.
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return \Magento\Framework\Model\AbstractModel  The saved model (may have a new ID)
     * @throws \Magento\Framework\Exception\CouldNotSaveException on persistence failure
     */
    public function save(\Magento\Framework\Model\AbstractModel $model): \Magento\Framework\Model\AbstractModel;

    /**
     * Delete a model.
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @throws \Magento\Framework\Exception\CouldNotDeleteException on persistence failure
     */
    public function delete(\Magento\Framework\Model\AbstractModel $model): void;

    /**
     * Delete by primary key — convenience wrapper.
     *
     * @param int|string $id
     * @throws \Magento\Framework\Exception\NoSuchEntityException  when not found
     * @throws \Magento\Framework\Exception\CouldNotDeleteException on persistence failure
     */
    public function deleteById(int|string $id): void;

    /**
     * Return a paginated, filtered, sorted list of models.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
