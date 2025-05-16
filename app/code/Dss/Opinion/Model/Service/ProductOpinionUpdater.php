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

namespace Dss\Opinion\Model\Service;

use Dss\Opinion\Model\OpinionFactory;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion as CustomerOpinionResource;
use Dss\Opinion\Model\ResourceModel\Opinion as ProductOpinionResource;
use Psr\Log\LoggerInterface;

class ProductOpinionUpdater
{
    /**
     * Constructor.
     *
     * @param CustomerOpinionResource $customerOpinionResource
     * @param ProductOpinionResource $productOpinionResource
     * @param OpinionFactory $productOpinionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CustomerOpinionResource $customerOpinionResource,
        protected ProductOpinionResource $productOpinionResource,
        protected OpinionFactory $productOpinionFactory,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Updates the like/dislike counts for a product.
     *
     * @param int $productId
     * @param string $productName
     * @return void
     */
    public function update(int $productId, string $productName): void
    {
        try {
            $connection = $this->customerOpinionResource->getConnection();
            $tableName = $this->customerOpinionResource->getMainTable();

            $select = $connection->select()
                ->from($tableName, [
                    'total_likes' => 'SUM(opinion = 1)',
                    'total_dislikes' => 'SUM(opinion = 0)'
                ])
                ->where('product_id = ?', $productId);

            $result = $connection->fetchRow($select);

            $totalLikes = (int)($result['total_likes'] ?? 0);
            $totalDislikes = (int)($result['total_dislikes'] ?? 0);

            $productOpinion = $this->productOpinionFactory->create();
            $this->productOpinionResource->load($productOpinion, $productId, 'product_id');

            if (!$productOpinion->getId()) {
                $productOpinion->setData([
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'total_like_opinion_count' => $totalLikes,
                    'total_dislike_opinion_count' => $totalDislikes
                ]);
            } else {
                $productOpinion->setTotalLikeOpinionCount($totalLikes);
                $productOpinion->setTotalDislikeOpinionCount($totalDislikes);
            }

            $this->productOpinionResource->save($productOpinion);
        } catch (\Exception $e) {
            $this->logger->error('Error updating product opinion counts: ' . $e->getMessage());
        }
    }
}
