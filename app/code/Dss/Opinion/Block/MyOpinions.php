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
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;

class MyOpinions extends AbstractOpinion
{
    /**
     * Constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $opinionCollectionFactory
     * @param Image $imageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        ProductRepositoryInterface $productRepository,
        protected CollectionFactory $opinionCollectionFactory,
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
        $collection = $this->opinionCollectionFactory->create()
            ->addFieldToFilter('customer_id', $this->config->getCustomerId());

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
     * Check if opinion statistics chart should be shown on product page
     *
     * @return bool
     */
    public function isOpinionStatsChartEnabled(): bool
    {
        return $this->config->isOpinionStatsChartEnabled();
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
        $currentPageOpinions = $this->getCustomerOpinions();
        $customerOpinions = $this->opinionCollectionFactory->create()
            ->addFieldToFilter('customer_id', $this->config->getCustomerId());
        $likeCount = 0;
        $currentPageLikeCount = 0;
        $dislikeCount = 0;
        $currentPageDislikeCount = 0;

        foreach ($customerOpinions as $opinion) {
            if ((int)$opinion->getOpinion() === 1) {
                $likeCount++;
            } else {
                $dislikeCount++;
            }
        }

        $likePercent = $likeCount > 0 ? round(
            ($likeCount / ($likeCount + $dislikeCount)) * 100,
            2
        ) : 0;

        foreach ($currentPageOpinions as $opinion) {
            if ((int)$opinion->getOpinion() === 1) {
                $currentPageLikeCount++;
            } else {
                $currentPageDislikeCount++;
            }
        }

        $currentLikePercent = $currentPageLikeCount > 0 ? round(
            ($currentPageLikeCount / ($currentPageLikeCount + $currentPageDislikeCount)) * 100,
            2
        ) : 0;

        return [
            'likes' => $likeCount,
            'like_percent' => $likePercent,
            'dislikes' => $dislikeCount,
            'current_page_likes' => $currentPageLikeCount,
            'current_page_like_percent' => $currentLikePercent,
            'current_page_dislikes' => $currentPageDislikeCount,
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
}
