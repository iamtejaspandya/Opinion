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

use Dss\Opinion\Model\ResourceModel\CustomerOpinion\Collection;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;

class MyOpinions extends Template
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CollectionFactory $opinionCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Image $imageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        private CustomerSession $customerSession,
        private CollectionFactory $opinionCollectionFactory,
        private ProductRepositoryInterface $productRepository,
        private Image $imageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve customer opinions collection
     *
     * @return Collection
     */
    public function getCustomerOpinions(): Collection
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->opinionCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);

        $currentPage = (int) ($this->getRequest()->getParam('p') ?? 1);
        $pageSize = (int) ($this->getRequest()->getParam('limit') ?? 5);

        $collection->setCurPage($currentPage);
        $collection->setPageSize($pageSize);

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
            )->setAvailableLimit([5 => 5, 10 => 10, 15 => 15])
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
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
}
