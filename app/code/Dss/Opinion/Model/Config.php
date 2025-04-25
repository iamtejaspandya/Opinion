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

namespace Dss\Opinion\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLE_PRODUCT_OPINION = 'opinion/general/enable_product_opinion';
    public const XML_PATH_GET_OPINION = 'opinion/general/get_opinion';
    public const XML_PATH_SHOW_MESSAGE = 'opinion/general/disabled_message';
    public const XML_PATH_DISALLOWED_CUSTOMER_MESSAGE = 'opinion/general/not_allow_message';
    public const XML_PATH_SHOW_OPINION_LABEL = 'opinion/opinion_label/show_opinion_label';
    public const XML_PATH_OPINION_LABEL_MIN_THRESHOLD = 'opinion/opinion_label/min_threshold';
    public const XML_PATH_OPINION_LABEL_MIN_LIKE = 'opinion/opinion_label/min_like';
    public const CUSTOMER_ATTRIBUTE_CODE = 'can_give_opinion';

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected CustomerRepositoryInterface $customerRepository,
        protected CustomerSession $customerSession
    ) {
    }

    /**
     * Check if product opinion functionality is enabled
     *
     * @return bool
     */
    public function isProductOpinionEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_PRODUCT_OPINION,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if customers are allowed to submit opinions
     *
     * @return bool
     */
    public function isOpinionSubmissionAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GET_OPINION,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the configured message to show when opinion submission is disabled
     *
     * @return string
     */
    public function getOpinionDisabledMessage(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SHOW_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the message configured for disallowed customers
     *
     * @return string
     */
    public function getDisallowedCustomerMessage(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_DISALLOWED_CUSTOMER_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if opinion label should be shown on product page
     *
     * @return bool
     */
    public function isOpinionLabelEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_OPINION_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get minimum threshold of opinions required to show opinion percentage
     *
     * @return int
     */
    public function getMinimumOpinionThreshold(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_OPINION_LABEL_MIN_THRESHOLD,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get minimum like percentage required to show opinion label
     *
     * @return int
     */
    public function getMinimumLikePercentage(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_OPINION_LABEL_MIN_LIKE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get customer ID if logged in.
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        return $this->customerSession->isLoggedIn()
            ? (int)$this->customerSession->getCustomerId()
            : null;
    }

    /**
     * Check if customer can give opinion.
     *
     * @return bool
     */
    public function canCustomerGiveOpinion(): bool
    {
        $customerId = $this->getCustomerId();
        if ($customerId === null) {
            return false;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $attribute = $customer->getCustomAttribute(self::CUSTOMER_ATTRIBUTE_CODE);

            return $attribute ? (bool)$attribute->getValue() : false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
