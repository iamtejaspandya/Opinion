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
use Magento\Framework\View\Element\Template;

abstract class AbstractOpinion extends Template
{
    /**
     * Constructor.
     *
     * @param Template\Context $context
     * @param Config $config
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        protected Config $config,
        protected ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
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
     * Get the configured message to show when opinion submission is disabled
     *
     * @return string
     */
    public function getOpinionDisabledMessage(): string
    {
        return $this->config->getOpinionDisabledMessage();
    }

    /**
     * Check if customer can give opinion.
     *
     * @return bool
     */
    public function canCustomerGiveOpinion(): bool
    {
        return $this->config->canCustomerGiveOpinion();
    }

    /**
     * Get opinion restriction message.
     *
     * @return string
     */
    public function getDisallowedCustomerMessage(): string
    {
        return $this->config->getDisallowedCustomerMessage();
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
}
