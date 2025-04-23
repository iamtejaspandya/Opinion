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

use Dss\Opinion\Model\ResourceModel\Opinion as OpinionResource;
use Magento\Framework\Model\AbstractModel;

class Opinion extends AbstractModel
{
    /**
     * @var string
     */
    protected $_idFieldName = 'opinion_id';

    /**
     * Initialize Opinion model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(OpinionResource::class);
    }
}
