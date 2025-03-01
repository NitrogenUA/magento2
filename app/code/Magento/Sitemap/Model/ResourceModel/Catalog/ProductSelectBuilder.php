<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Sitemap\Model\ResourceModel\Catalog;

use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Api\Data\StoreInterface;

class ProductSelectBuilder
{
    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Allow to modify a select statement with plugins
     *
     * @param string $mainTableName
     * @param string $idField
     * @param string $linkField
     * @param StoreInterface $store
     * @return Select
     */
    public function execute(
        string $mainTableName,
        string $idField,
        string $linkField,
        StoreInterface $store
    ): Select {
        $connection = $this->resource->getConnection();

        return $connection->select()->from(
            ['e' => $mainTableName],
            [$idField, $linkField, 'updated_at']
        )->joinInner(
            ['w' => $this->resource->getTableName('catalog_product_website')],
            'e.entity_id = w.product_id',
            []
        )->joinLeft(
            ['url_rewrite' => $this->resource->getTableName('url_rewrite')],
            'e.entity_id = url_rewrite.entity_id AND url_rewrite.is_autogenerated = 1'
            . ' AND url_rewrite.metadata IS NULL'
            . $connection->quoteInto(' AND url_rewrite.store_id = ?', $store->getId())
            . $connection->quoteInto(' AND url_rewrite.entity_type = ?', ProductUrlRewriteGenerator::ENTITY_TYPE),
            ['url' => 'request_path']
        )->where(
            'w.website_id = ?',
            $store->getWebsiteId()
        );
    }
}
