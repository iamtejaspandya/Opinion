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

namespace Dss\Opinion\Model\ResourceModel;

use Dss\Opinion\Model\CustomerOpinion as CustomerOpinionModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CustomerOpinion extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('dss_customer_product_opinion', 'customer_opinion_id');
    }

    /**
     * Load opinion data by customer ID and product ID
     *
     * @param CustomerOpinionModel $opinion
     * @param int $customerId
     * @param int $productId
     * @return void
     */
    public function loadByCustomerAndProduct(
        CustomerOpinionModel $opinion,
        int $customerId,
        int $productId
    ): void {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('product_id = ?', $productId);

        $data = $connection->fetchRow($select);

        if ($data) {
            $opinion->setData($data);
        }
    }
}
