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

use Dss\Opinion\Model\Config;
use Dss\Opinion\Model\CustomerOpinionFactory;
use Dss\Opinion\Model\CustomerOpinion as CustomerOpinionModel;
use Dss\Opinion\Model\OpinionFactory as ProductOpinionFactory;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion as CustomerOpinionResource;
use Dss\Opinion\Model\ResourceModel\Opinion as ProductOpinionResource;
use Psr\Log\LoggerInterface;

class OpinionManager
{
    /**
     * Constructor.
     *
     * @param Config $config
     * @param CustomerOpinionFactory $customerOpinionFactory
     * @param CustomerOpinionResource $customerOpinionResource
     * @param ProductOpinionResource $productOpinionResource
     * @param ProductOpinionFactory $productOpinionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Config $config,
        protected CustomerOpinionFactory $customerOpinionFactory,
        protected CustomerOpinionResource $customerOpinionResource,
        protected ProductOpinionResource $productOpinionResource,
        protected ProductOpinionFactory $productOpinionFactory,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Load opinion by customer and product.
     *
     * @param int $customerId
     * @param int $productId
     * @return CustomerOpinionModel
     */
    public function loadByCustomerAndProduct(
        int $customerId,
        int $productId
    ): CustomerOpinionModel {
        $opinion = $this->customerOpinionFactory->create();
        $this->customerOpinionResource->loadByCustomerAndProduct(
            $opinion,
            $customerId,
            $productId
        );

        return $opinion;
    }

    /**
     * Save or update an customer opinion.
     *
     * @param int $customerId
     * @param int $productId
     * @param string $productName
     * @param int $newOpinion
     * @return array ['success' => bool, 'message' => string, 'opinion' => int|null]
     * @throws \Exception
     */
    public function customerOpinionSave(
        int $customerId,
        int $productId,
        string $productName,
        int $newOpinion
    ): array {
        try {
            $opinion = $this->loadByCustomerAndProduct($customerId, $productId);

            if ($opinion->getId()) {
                if ((int)$opinion->getOpinion() === $newOpinion) {
                    return [
                        'success' => true,
                        'message' => __('Your opinion is already submitted.'),
                        'opinion' => $newOpinion
                    ];
                }
                $opinion->setOpinion($newOpinion);
                $opinion->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            } else {
                $opinion->setData([
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'opinion' => $newOpinion
                ]);
            }

            $this->customerOpinionResource->save($opinion);
            $this->productOpinionUpdate($productId, $productName);

            return [
                'success' => true,
                'message' => __('Your opinion has been submitted successfully.'),
                'opinion' => $newOpinion
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error saving opinion: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => __('Something went wrong while saving your opinion.'),
                'opinion' => null
            ];
        }
    }

    /**
     * Delete opinion if owned by customer.
     *
     * @param int $customerId
     * @param int $opinionId
     * @return array ['success' => bool, 'message' => string]
     * @throws \Exception
     */
    public function customerOpinionDelete(
        int $customerId,
        int $opinionId
    ): array {
        try {
            $opinion = $this->customerOpinionFactory->create();
            $this->customerOpinionResource->load($opinion, $opinionId);

            if (!$opinion->getId()) {
                return [
                    'success' => false,
                    'message' => __('Opinion not found.'),
                ];
            }

            if ((int)$opinion->getCustomerId() !== $customerId) {
                return [
                    'success' => false,
                    'message' => __('You are not authorized to delete this opinion.'),
                ];
            }

            $productId = (int)$opinion->getProductId();
            $productName = (string)$opinion->getProductName();

            $this->customerOpinionResource->delete($opinion);
            $this->productOpinionUpdate($productId, $productName);

            return [
                'success' => true,
                'message' => __('Opinion deleted successfully.')
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error deleting opinion: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => __('Something went wrong while deleting the opinion.')
            ];
        }
    }

    /**
     * Updates the like/dislike counts for a product.
     *
     * @param int $productId
     * @param string $productName
     * @return void
     */
    public function productOpinionUpdate(
        int $productId,
        string $productName
    ): void {
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

    /**
     * Get personalized product opinion label.
     *
     * @param int $productId
     * @param int|null $customerId
     * @return array
     */
    public function getProductOpinionLabel(
        int $productId,
        ?int $customerId = null
    ): array {
        $opinion = $this->productOpinionFactory->create()->load($productId, 'product_id');
        $totalLikes = (int) $opinion->getTotalLikeOpinionCount();
        $totalDislikes = (int) $opinion->getTotalDislikeOpinionCount();
        $totalOpinions = $totalLikes + $totalDislikes;

        $customerOpinion = null;
        if ($customerId !== null) {
            $customerOpinion = $this->loadByCustomerAndProduct($customerId, $productId)->getOpinion();
        }

        $percentage = $totalOpinions ? round(($totalLikes / $totalOpinions) * 100) : 0;
        $minThreshold = $this->config->getMinimumOpinionThreshold();
        $minLike = $this->config->getMinimumLikePercentage();
        $message = '';
        $class = '';

        if ($totalOpinions === 0) {
            $message = __('Be the first to share your opinion!');
            $class = 'no-opinion';
        } elseif ($totalOpinions === 1) {
            $message = $customerOpinion !== null
                ? ($customerOpinion ? __('First opinion in — and it’s a thumbs-up!')
                                    : __('First opinion in — not your favorite.'))
                : __('One opinion in! Share yours!');
            $class = 'one-opinion';
        } elseif ($totalOpinions < $minThreshold) {
            if ($totalLikes > 0 && $totalDislikes === 0) {
                $message = $customerOpinion !== null && $customerOpinion
                    ? __('You liked this—waiting for more opinions!')
                    : __('Liked by some of our customers.');
                $class = 'someliked';
            } elseif ($totalLikes === 0 && $totalDislikes > 0) {
                $message = $customerOpinion !== null && !$customerOpinion
                    ? __('Not your pick! Others haven’t shared yet.')
                    : __('More opinions needed! What do you think?');
                $class = 'not-enough';
            } else {
                $message = $customerOpinion !== null
                    ? ($customerOpinion ? __('This product got your like!, but opinions are mixed.')
                                        : __('Not your favorite, but others had mixed opinions.'))
                    : __('This product has received mixed opinions.');
                $class = 'mixed';
            }
        } else {
            if ($percentage >= $minLike) {
                $message = $customerOpinion !== null
                    ? ($customerOpinion
                        ? __(
                            'You and %1% of our %2 customers liked this product',
                            $percentage,
                            $totalOpinions
                        )
                        : __(
                            'Not your favorite, but %1% of our %2 customers liked it',
                            $percentage,
                            $totalOpinions
                        ))
                    : __(
                        '%1% of our %2 customers liked this product',
                        $percentage,
                        $totalOpinions
                    )
                ;
                $class = 'liked';
            } else {
                $message = $customerOpinion !== null
                    ? ($customerOpinion ? __('This product got your like!, but opinions are mixed.')
                                        : __('Not your favorite, but others had mixed opinions.'))
                    : __('This product has received mixed opinions.');
                $class = 'mixed';
            }
        }

        return [
            'success' => true,
            'percentage' => $percentage,
            'total_opinions' => $totalOpinions,
            'message' => $message,
            'class' => $class
        ];
    }
}
