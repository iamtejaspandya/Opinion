<?php

declare(strict_types=1);

/**
 * Digit Software Solutions.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @category  Dss
 * @package   Dss_Opinion
 * @author    Extension Team
 * @copyright Copyright (c) 2025 Digit Software Solutions. ( https://digitsoftsol.com )
 */

namespace Dss\Opinion\Model\ResourceModel\Opinion;

use Dss\Opinion\Model\Opinion as OpinionModel;
use Dss\Opinion\Model\ResourceModel\Opinion as OpinionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(OpinionModel::class, OpinionResource::class);
    }

    /**
     * Get total like count for a product
     *
     * @param int $productId
     * @return int
     */
    public function getLikeCount(int $productId): int
    {
        return (int)$this->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('opinion', 1)
            ->getSize();
    }

    /**
     * Get total dislike count for a product
     *
     * @param int $productId
     * @return int
     */
    public function getDislikeCount(int $productId): int
    {
        return (int)$this->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('opinion', 0)
            ->getSize();
    }
}
