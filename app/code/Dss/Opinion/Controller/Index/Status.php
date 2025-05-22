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
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Status implements HttpGetActionInterface
{
    /**
     * Constructor.
     *
     * @param CustomerSession $customerSession
     * @param JsonFactory $jsonFactory
     * @param OpinionManager $opinionManager
     * @param RequestInterface $request
     */
    public function __construct(
        protected CustomerSession $customerSession,
        protected JsonFactory $jsonFactory,
        protected OpinionManager $opinionManager,
        protected RequestInterface $request
    ) {
    }

    /**
     * Execute method to retrieve the opinion status.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['opinion' => null]);
        }

        $productId = (int)$this->request->getParam('product_id');
        if (!$productId) {
            return $result->setData(['opinion' => null]);
        }

        $customerId = (int)$this->customerSession->getCustomerId();
        $opinion = $this->opinionManager->loadByCustomerAndProduct($customerId, $productId);

        return $result->setData([
            'opinion' => $opinion->getOpinion() !== null ? (int)$opinion->getOpinion() : null
        ]);
    }
}
