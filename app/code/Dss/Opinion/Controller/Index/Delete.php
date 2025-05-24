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
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class Delete implements HttpPostActionInterface
{
    /**
     * Constructor.
     *
     * @param Config $config
     * @param CustomerSession $customerSession
     * @param ManagerInterface $messageManager
     * @param OpinionManager $opinionManager
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param UrlInterface $url
     */
    public function __construct(
        protected Config $config,
        protected CustomerSession $customerSession,
        protected ManagerInterface $messageManager,
        protected OpinionManager $opinionManager,
        protected RequestInterface $request,
        protected JsonFactory $jsonFactory,
        protected UrlInterface $url
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
        $opinionId = (int)$this->request->getParam('opinion_id');
        $myOpinionsUrl = $this->request->getServer('HTTP_REFERER') ?? '';

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(
                __('Looks like you\'re logged out. Please sign in to share your thoughts!')
            );

            return $result->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $this->url->getUrl('customer/account/login')
            ]);
        }

        if (!$this->config->isProductOpinionEnabled()) {
            $this->messageManager->addErrorMessage(
                __('The product opinion feature is currently disabled.')
            );

            return $result->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $myOpinionsUrl
            ]);
        }

        if (!$this->config->isOpinionSubmissionAllowed()) {
            $this->messageManager->addErrorMessage(
                __('We appreciate your opinion! However, submissions are currently turned off.')
            );

            return $result->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $myOpinionsUrl
            ]);
        }

        if (!$this->config->canCustomerGiveOpinion()) {
            $this->messageManager->addErrorMessage(
                __('Your account is currently restricted from submitting opinions.')
            );

            return $result->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $myOpinionsUrl
            ]);
        }

        $customerId = (int)$this->customerSession->getCustomerId();
        $response = $this->opinionManager->customerOpinionDelete($customerId, $opinionId);

        if ($response['success']) {
            $this->messageManager->addSuccessMessage(__($response['message']));
            return $result->setData([
                'success' => true,
                'redirect' => true,
                'redirect_url' => $myOpinionsUrl
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
