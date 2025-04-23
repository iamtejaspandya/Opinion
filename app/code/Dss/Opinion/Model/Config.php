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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLE_PRODUCT_OPINION = 'opinion/general/enable_product_opinion';
    public const XML_PATH_GET_OPINION = 'opinion/general/get_opinion';
    public const XML_PATH_SHOW_OPINION_LABEL = 'opinion/opinion_label/show_opinion_label';
    public const XML_PATH_OPINION_LABEL_MIN_THRESHOLD = 'opinion/opinion_label/min_threshold';
    public const XML_PATH_OPINION_LABEL_MIN_LIKE = 'opinion/opinion_label/min_like';

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
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
}
