<?php

declare(strict_types=1);

namespace BetterMagento\Core\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Generic "Actions" column for BetterMagento admin grids.
 *
 * Renders View, Edit and Delete links for any BM entity grid.
 * All actions are optional and configuration-driven.
 * Configure via the UI component XML:
 *
 *   <column name="actions" class="BetterMagento\Core\Ui\Component\Listing\Column\BmActions">
 *       <settings>
 *           <label translate="true">Actions</label>
 *       </settings>
 *       <argument name="data" xsi:type="array">
 *           <item name="config" xsi:type="array">
 *               <item name="viewUrlPath" xsi:type="string">mymodule/entity/view</item>
 *               <item name="editUrlPath" xsi:type="string">mymodule/entity/edit</item>
 *               <item name="deleteUrlPath" xsi:type="string">mymodule/entity/delete</item>
 *               <item name="indexField" xsi:type="string">entity_id</item>
 *           </item>
 *       </argument>
 *   </column>
 */
class BmActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $config       = $this->getData('config');
        $viewUrl      = (string) ($config['viewUrlPath'] ?? '');
        $editUrl      = (string) ($config['editUrlPath'] ?? '*/*/edit');
        $deleteUrl    = (string) ($config['deleteUrlPath'] ?? '*/*/delete');
        $indexField   = (string) ($config['indexField'] ?? 'id');
        $columnName   = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $id = $item[$indexField] ?? null;
            if ($id === null) {
                continue;
            }

            if ($viewUrl) {
                $item[$columnName]['view'] = [
                    'href'  => $this->urlBuilder->getUrl($viewUrl, [$indexField => $id]),
                    'label' => __('View'),
                ];
            }

            $item[$columnName]['edit'] = [
                'href'  => $this->urlBuilder->getUrl($editUrl, [$indexField => $id]),
                'label' => __('Edit'),
            ];
            $item[$columnName]['delete'] = [
                'href'    => $this->urlBuilder->getUrl($deleteUrl, [$indexField => $id]),
                'label'   => __('Delete'),
                'confirm' => [
                    'title'   => __('Delete'),
                    'message' => __('Are you sure you want to delete this record?'),
                ],
                'post'    => true,
            ];
        }
        unset($item);

        return $dataSource;
    }
}
