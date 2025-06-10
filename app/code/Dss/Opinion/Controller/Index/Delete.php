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

use Dss\Opinion\Model\Config;
use Dss\Opinion\Model\Service\OpinionManager;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

class Delete implements HttpPostActionInterface
{
    /**
     * Constructor.
     *
     * @param Config $config
     * @param JsonFactory $jsonFactory
     * @param ManagerInterface $messageManager
     * @param OpinionManager $opinionManager
     * @param RequestInterface $request
     */
    public function __construct(
        protected Config $config,
        protected JsonFactory $jsonFactory,
        protected ManagerInterface $messageManager,
        protected OpinionManager $opinionManager,
        protected RequestInterface $request
    ) {
    }

    /**
     * Delete customer opinion action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();
        $refererUrl = $this->request->getServer('HTTP_REFERER') ?? '';

        $validation = $this->opinionManager->canCustomerSubmitOpinion($refererUrl);

        if (!$validation['valid']) {
            return $result->setData($validation['response']);
        }

        $customerId = (int)$this->config->getCustomerId();
        $opinionId = (int)$this->request->getParam('opinion_id');

        $response = $this->opinionManager->customerOpinionDelete(
            $customerId,
            $opinionId
        );

        if ($response['success']) {
            $this->messageManager->addSuccessMessage(__($response['message']));

            return $result->setData([
                'success' => true,
                'redirect' => true,
                'redirect_url' => $refererUrl
            ]);
        } else {
            $this->messageManager->addErrorMessage(__($response['message']));

            return $result->setData([
                'success' => false,
                'redirect' => false
            ]);
        }
    }
}
