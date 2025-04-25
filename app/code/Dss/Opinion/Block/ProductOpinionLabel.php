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

class ProductOpinionLabel extends AbstractOpinion
{
    /**
     * Check if opinion label should be shown on product page
     *
     * @return bool
     */
    public function isOpinionLabelEnabled(): bool
    {
        return $this->config->isOpinionLabelEnabled();
    }

    /**
     * Get the AJAX URL for product opinion label.
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('opinion/index/productopinionlabel');
    }
}
