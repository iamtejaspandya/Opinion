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
use Magento\Bundle\Model\Selection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
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
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $productCollectionFactory
     * @param Configurable $configurableType
     * @param Grouped $groupedType
     * @param Selection $bundleSelection
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Config $config,
        protected CustomerOpinionFactory $customerOpinionFactory,
        protected CustomerOpinionResource $customerOpinionResource,
        protected ProductOpinionResource $productOpinionResource,
        protected ProductOpinionFactory $productOpinionFactory,
        protected ProductRepositoryInterface $productRepository,
        protected CollectionFactory $productCollectionFactory,
        protected Configurable $configurableType,
        protected Grouped $groupedType,
        protected Selection $bundleSelection,
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
     * @param int $newOpinion
     * @return array ['success' => bool, 'message' => string, 'opinion' => int|null]
     * @throws \Exception
     */
    public function customerOpinionSave(
        int $customerId,
        int $productId,
        int $newOpinion
    ): array {
        try {
            $opinion = $this->loadByCustomerAndProduct($customerId, $productId);
            $productName = $this->getProductNameById($productId);

            if ($productName === null) {
                return [
                    'success' => false,
                    'message' => __('Product name not found.'),
                    'opinion' => null
                ];
            }

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
            $this->productOpinionUpdate($productId);

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
            $this->customerOpinionResource->delete($opinion);
            $this->productOpinionUpdate($productId);

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
     * @return void
     */
    public function productOpinionUpdate(int $productId): void
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

            $productName = $this->getProductNameById($productId);
            if ($productName === null) {
                return;
            }

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
                                    : __('First opinion in — not your favorite'))
                : __('One opinion in! Share yours!');
            $class = 'one-opinion';
        } elseif ($totalOpinions < $minThreshold) {
            if ($totalLikes > 0 && $totalDislikes === 0) {
                $message = $customerOpinion !== null && $customerOpinion
                    ? __('You liked this—waiting for more opinions!')
                    : __('Liked by some of our customers');
                $class = 'someliked';
            } elseif ($totalLikes === 0 && $totalDislikes > 0) {
                $message = $customerOpinion !== null && !$customerOpinion
                    ? __('Not your pick! Others haven’t shared yet')
                    : __('More opinions needed! What do you think?');
                $class = 'not-enough';
            } else {
                $message = $customerOpinion !== null
                    ? ($customerOpinion ? __('This product got your like!, but opinions are mixed')
                                        : __('Not your favorite, but others had mixed opinions'))
                    : __('This product has received mixed opinions');
                $class = 'mixed';
            }
        } else {
            if ($totalDislikes === 0) {
                $message = $customerOpinion !== null && $customerOpinion
                    ? __(
                        'Everyone’s loving it — %1% out of %2 customers liked this!',
                        $percentage,
                        $totalOpinions
                    )
                    : __(
                        'Loved by everyone — %1% of %2 customers liked it',
                        $percentage,
                        $totalOpinions
                    )
                ;
                $class = 'all-liked';
            } elseif ($percentage >= $minLike) {
                $message = $customerOpinion !== null
                    ? ($customerOpinion
                        ? __(
                            'Great pick! %1% of our %2 customers agree with you',
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
                    ? ($customerOpinion ? __('This product got your like!, but opinions are mixed')
                                        : __('Not your favorite, but others had mixed opinions'))
                    : __('This product has received mixed opinions');
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

    /**
     * Get product name by product ID.
     *
     * @param int $productId
     * @return string|null
     */
    public function getProductNameById(int $productId): ?string
    {
        try {
            $product = $this->productRepository->getById($productId);
            return $product->getName();
        } catch (NoSuchEntityException $e) {
            $this->logger->warning("Product with ID $productId not found.");
            return null;
        } catch (\Exception $e) {
            $this->logger->error("Error fetching product name for ID $productId: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve a filtered product collection based on a name query.
     *
     * Filters by:
     * - Product name (LIKE %query%)
     * - Status: enabled
     * - Visibility: catalog, search, or both
     * - Excludes child products of configurable, grouped, and bundle products
     *
     * @param string $query
     * @return Collection
     */
    public function getFilteredProductCollection(string $query): Collection
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('name', ['like' => '%' . $query . '%'])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ]]);

        $productIds = $collection->getAllIds();

        $childProductIds = [];

        $childProductIds = array_merge(
            $childProductIds,
            ...array_values((array) $this->configurableType->getChildrenIds($productIds)),
            ...array_values((array) $this->groupedType->getChildrenIds($productIds)),
            ...array_values((array) $this->bundleSelection->getChildrenIds($productIds))
        );

        if (!empty($childProductIds)) {
            $collection->addFieldToFilter('entity_id', ['nin' => $childProductIds]);
        }

        return $collection;
    }

    /**
     * Get an array of matching product IDs after filtering by name and excluding child products.
     *
     * @param string $query
     * @return int[]
     */
    public function getMatchingProductIds(string $query): array
    {
        return $this->getFilteredProductCollection($query)->getAllIds();
    }
}
