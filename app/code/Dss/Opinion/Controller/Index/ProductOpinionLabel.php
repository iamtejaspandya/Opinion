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

namespace Dss\Opinion\Controller\Index;

use Dss\Opinion\Model\Service\OpinionManager;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class ProductOpinionLabel implements HttpGetActionInterface
{
    /**
     * Constructor.
     *
     * @param Session $customerSession
     * @param JsonFactory $jsonFactory
     * @param OpinionManager $opinionManager
     * @param RequestInterface $request
     */
    public function __construct(
        protected Session $customerSession,
        protected JsonFactory $jsonFactory,
        protected OpinionManager $opinionManager,
        protected RequestInterface $request
    ) {
    }

    /**
     * Execute method to retrieve the product opinion label.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $productId = (int) $this->request->getParam('product_id');

        if (!$productId) {
            return $result->setData(['error' => true, 'message' => __('Invalid product.'), 'class' => 'error']);
        }

        $customerId = null;
        if ($this->customerSession->isLoggedIn()) {
            $customerId = (int)$this->customerSession->getCustomerId();
        }

        $response = $this->opinionManager->getProductOpinionLabel($productId, $customerId);

        return $result->setData($response);
    }
}
