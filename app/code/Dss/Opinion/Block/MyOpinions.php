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

namespace Dss\Opinion\Block;

use Dss\Opinion\Model\Config;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion\Collection;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion\CollectionFactory;
use Dss\Opinion\Model\Service\OpinionManager;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;

class MyOpinions extends AbstractOpinion
{
    /**
     * @var bool
     */
    protected $noProductMatch = false;

    /**
     * @var int
     */
    protected $matchedOpinionCount = 0;

    /**
     * @var string|null
     */
    protected $searchQuery;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $opinionCollectionFactory
     * @param OpinionManager $opinionManager
     * @param Image $imageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        ProductRepositoryInterface $productRepository,
        protected CollectionFactory $opinionCollectionFactory,
        protected OpinionManager $opinionManager,
        protected Image $imageHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $config,
            $productRepository,
            $data
        );
    }

    /**
     * Retrieve customer opinions collection
     *
     * @return Collection
     */
    public function getCustomerOpinions(): Collection
    {
        $searchQuery = $this->getSearchQuery();
        $collection = $this->opinionCollectionFactory->create()
            ->addFieldToFilter('customer_id', $this->config->getCustomerId());

        if ($searchQuery) {
            $matchingProductIds = $this->getMatchingProductIdsByName($searchQuery);

            if (!empty($matchingProductIds)) {
                $collection->addFieldToFilter('product_id', ['in' => $matchingProductIds]);
                $this->matchedOpinionCount = $collection->getSize();

                if ($this->matchedOpinionCount === 0) {
                    $this->noProductMatch = true;

                    $collection = $this->opinionCollectionFactory->create()
                        ->addFieldToFilter('customer_id', $this->config->getCustomerId());
                }
            } else {
                $this->noProductMatch = true;
            }
        }

        $collection->setCurPage((int)($this->getRequest()->getParam('p') ?? 1));
        $collection->setPageSize((int)($this->getRequest()->getParam('limit') ?? 5));

        return $collection;
    }

    /**
     * Retrieve product details by product ID
     *
     * @param int $productId
     * @return ProductInterface|null
     */
    public function getProductById(int $productId): ?ProductInterface
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Retrieve product image URL
     *
     * @param ProductInterface $product
     * @return string
     */
    public function getImageUrl(ProductInterface $product): string
    {
        return $this->imageHelper->init($product, 'category_page_grid')->getUrl();
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $collection = $this->getCustomerOpinions();
        if ($collection->getSize()) {
            $pager = $this->getLayout()->createBlock(
                Pager::class,
                'product.opinion.pager'
            )->setAvailableLimit([
                    5 => 5,
                    10 => 10,
                    15 => 15,
                    20 => 20
                ])
                ->setShowPerPage(true)
                ->setCollection($collection);

            $this->setChild('pager', $pager);
        }

        return $this;
    }

    /**
     * Get Pager child block output
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Check if opinion chart should be shown on product page
     *
     * @return bool
     */
    public function isOpinionChartEnabled(): bool
    {
        return $this->config->isOpinionChartEnabled();
    }

    /**
     * Check if current opinion chart should be shown
     *
     * @return bool
     */
    public function isCurrentOpinionChartEnabled(): bool
    {
        return $this->config->isCurrentOpinionChartEnabled();
    }

    /**
     * Check if opinion chart total should be shown
     *
     * @return bool
     */
    public function isOpinionChartTotalEnabled(): bool
    {
        return $this->config->isOpinionChartTotalEnabled();
    }

    /**
     * Check if opinion chart percentage should be shown
     *
     * @return bool
     */
    public function isOpinionChartPercentageEnabled(): bool
    {
        return $this->config->isOpinionChartPercentageEnabled();
    }

    /**
     * Get opinion statistics for current customer.
     *
     * @return array
     */
    public function getOpinionStats(): array
    {
        $searchQuery = $this->getSearchQuery();
        $currentPageOpinions = $this->getCustomerOpinions();
        $customerId = $this->config->getCustomerId();

        $baseOpinions = $this->opinionCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);
        $totalOpinions = clone $baseOpinions;

        $searchFiltered = false;
        if ($searchQuery) {
            $matchingProductIds = $this->getMatchingProductIdsByName($searchQuery);
            if (!empty($matchingProductIds)) {
                $totalOpinions->addFieldToFilter('product_id', ['in' => $matchingProductIds]);
                $searchFiltered = true;
            } else {
                $searchFiltered = false;
            }
        }

        if ($searchFiltered && $totalOpinions->getSize() === 0) {
            $totalOpinions = $baseOpinions;
        }

        $likeCount = 0;
        $dislikeCount = 0;
        foreach ($totalOpinions as $opinion) {
            if ((int)$opinion->getOpinion() === 1) {
                $likeCount++;
            } else {
                $dislikeCount++;
            }
        }

        $currentPageLikeCount = 0;
        $currentPageDislikeCount = 0;
        foreach ($currentPageOpinions as $opinion) {
            if ((int)$opinion->getOpinion() === 1) {
                $currentPageLikeCount++;
            } else {
                $currentPageDislikeCount++;
            }
        }

        $likePercent = ($likeCount + $dislikeCount) > 0
            ? round(($likeCount / ($likeCount + $dislikeCount)) * 100, 2)
            : 0;

        $currentLikePercent = ($currentPageLikeCount + $currentPageDislikeCount) > 0
            ? round(($currentPageLikeCount / ($currentPageLikeCount + $currentPageDislikeCount)) * 100, 2)
            : 0;

        return [
            'likes' => $likeCount,
            'dislikes' => $dislikeCount,
            'like_percent' => $likePercent,
            'current_page_likes' => $currentPageLikeCount,
            'current_page_dislikes' => $currentPageDislikeCount,
            'current_page_like_percent' => $currentLikePercent,
            'total' => $likeCount + $dislikeCount,
            'current_page_total' => $currentPageLikeCount + $currentPageDislikeCount,
        ];
    }

    /**
     * Get opinion chart colors from configuration
     *
     * @return array
     */
    public function getOpinionChartColors(): array
    {
        return $this->config->getOpinionChartColors();
    }

    /**
     * Get current opinion chart colors from configuration
     *
     * @return array
     */
    public function getCurrentOpinionColors(): array
    {
        return $this->config->getCurrentOpinionColors();
    }

    /**
     * Get search query from request parameters
     *
     * @return string
     */
    public function getSearchQuery(): string
    {
        if ($this->searchQuery === null) {
            $this->searchQuery = trim((string) $this->getRequest()->getParam('q'));
        }

        return $this->searchQuery;
    }

    /**
     * Retrieve product IDs matching the search query by product name
     *
     * @param string $query
     * @return array
     */
    protected function getMatchingProductIdsByName(string $query): array
    {
        $productIdsParam = $this->getRequest()->getParam('product_ids');

        if ($productIdsParam) {
            return array_filter(explode(',', $productIdsParam), 'is_numeric');
        }

        return $this->opinionManager->getMatchingProductIds($query);
    }

    /**
     * Check if there are no product matches for the search query
     *
     * @return bool
     */
    public function hasNoProductMatch(): bool
    {
        return $this->noProductMatch ?? false;
    }

    /**
     * Get the count of matched opinions based on the search query
     *
     * @return int
     */
    public function getMatchedOpinionCount(): int
    {
        return $this->matchedOpinionCount;
    }
}
