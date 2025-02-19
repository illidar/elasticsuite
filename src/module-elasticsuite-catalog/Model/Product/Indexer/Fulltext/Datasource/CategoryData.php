<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData as ResourceModel;

/**
 * Datasource used to append categories data to product during indexing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryData implements DatasourceInterface
{
    /**
     * @var array
     */
    private $categoriesUid = [];

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @param ResourceModel $resourceModel Resource model
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Add categories data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $categoryData = $this->resourceModel->loadCategoryData($storeId, array_keys($indexData));

        foreach ($categoryData as $categoryDataRow) {
            $productId = (int) $categoryDataRow['product_id'];
            unset($categoryDataRow['product_id']);

            $categoryDataRow = array_merge(
                $categoryDataRow,
                [
                    'category_id'   => (int) $categoryDataRow['category_id'],
                    'category_uid'  => $this->getUidFromLocalCache((int) $categoryDataRow['category_id']),
                    'is_parent'     => (bool) $categoryDataRow['is_parent'],
                    'name'          => (string) $categoryDataRow['name'],
                ]
            );

            if (isset($categoryDataRow['position']) && $categoryDataRow['position'] !== null) {
                $categoryDataRow['position'] = (int) $categoryDataRow['position'];
            }

            if (isset($categoryDataRow['is_blacklisted'])) {
                $categoryDataRow['is_blacklisted'] = (bool) $categoryDataRow['is_blacklisted'];
            }

            $indexData[$productId]['category'][] = array_filter($categoryDataRow);
        }

        return $indexData;
    }

    /**
     * Gets category uid from local cache by category id.
     *
     * @param int $categoryId Category id
     * @return string
     */
    private function getUidFromLocalCache(int $categoryId): string
    {
        if (!isset($this->categoriesUid[$categoryId])) {
            // BC : Use Magento\Framework\GraphQl\Query\Uid once we drop support for Magento 2.4.1.
            $this->categoriesUid[$categoryId] = base64_encode((string) $categoryId);
        }

        return $this->categoriesUid[$categoryId];
    }
}
