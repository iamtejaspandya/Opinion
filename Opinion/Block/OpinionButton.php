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
use Dss\Opinion\Model\ResourceModel\CustomerOpinion\CollectionFactory as OpinionCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class OpinionButton extends Template
{
    /**
     * @param Context $context
     * @param Config $Config
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param HttpContext $httpContext
     * @param OpinionCollectionFactory $opinionCollectionFactory
     * @param EncoderInterface $urlEncoder
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Config $Config,
        private ProductRepositoryInterface $productRepository,
        private CustomerRepositoryInterface $customerRepository,
        private HttpContext $httpContext,
        private OpinionCollectionFactory $opinionCollectionFactory,
        private EncoderInterface $urlEncoder,
        private UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Check if product opinion functionality is enabled
     *
     * @return bool
     */
    public function isProductOpinionEnabled(): bool
    {
        return $this->Config->isProductOpinionEnabled();
    }

    /**
     * Get Product ID
     *
     * @return int|null
     */
    public function getProductId(): ?int
    {
        return (int) $this->getRequest()->getParam('id');
    }

    /**
     * Get Product Name
     *
     * @return string
     */
    public function getProductName(): string
    {
        try {
            $product = $this->productRepository->getById($this->getProductId());
            return $product->getName();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return (bool) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Get Customer Data
     *
     * @return CustomerInterface|null
     */
    public function getCustomerData(): ?CustomerInterface
    {
        if ($this->isCustomerLoggedIn()) {
            $customerId = $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
            return $this->customerRepository->getById($customerId);
        }
        return null;
    }

    /**
     * Get Customer Opinion
     *
     * @return int|null
     */
    public function getCustomerOpinion(): ?int
    {
        if (!$this->isCustomerLoggedIn()) {
            return null;
        }

        $customerId = (int) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);

        $collection = $this->opinionCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('product_id', $this->getProductId());

        return (int) $collection->getFirstItem()->getOpinion() ?: null;
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormAction(): string
    {
        return $this->getUrl('opinion/index/save');
    }

    /**
     * Get current URL encoded for redirect after login
     *
     * @return string
     */
    public function getCurrentUrlEncoded(): string
    {
        return $this->urlEncoder->encode($this->urlBuilder->getCurrentUrl());
    }
}
