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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\UrlInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\Template\Context;

class OpinionButton extends AbstractOpinion
{
    /**
     * Constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param ProductRepositoryInterface $productRepository
     * @param Context $httpContext
     * @param EncoderInterface $urlEncoder
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        ProductRepositoryInterface $productRepository,
        protected HttpContext $httpContext,
        protected EncoderInterface $urlEncoder,
        protected UrlInterface $urlBuilder,
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
     * Check if product opinion is enabled.
     *
     * @return bool
     */
    public function isProductOpinionEnabled(): bool
    {
        return $this->config->isProductOpinionEnabled();
    }

    /**
     * Check if opinion submission is allowed.
     *
     * @return bool
     */
    public function isOpinionSubmissionAllowed(): bool
    {
        return $this->config->isOpinionSubmissionAllowed();
    }

    /**
     * Get the configured message to show when opinion submission is disabled
     *
     * @return string
     */
    public function getOpinionDisabledMessage(): string
    {
        return $this->config->getOpinionDisabledMessage();
    }

    /**
     * Get Product ID from request.
     *
     * @return int|null
     */
    public function getProductId(): ?int
    {
        return (int)$this->getRequest()->getParam('id');
    }

    /**
     * Get the product name.
     *
     * @return string
     */
    public function getProductName(): string
    {
        try {
            return $this->productRepository->getById($this->getProductId())->getName();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if the customer is logged in.
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Get the URL for the opinion submission form.
     *
     * @return string
     */
    public function getCurrentUrlEncoded(): string
    {
        return $this->urlEncoder->encode($this->urlBuilder->getCurrentUrl());
    }
}
