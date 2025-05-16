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
use Dss\Opinion\Model\CustomerOpinionFactory;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion as CustomerOpinionResource;
use Dss\Opinion\Model\Service\ProductOpinionUpdater;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Delete implements HttpPostActionInterface
{
    /**
     * Constructor.
     *
     * @param Config $config
     * @param ProductOpinionUpdater $productOpinionUpdater
     * @param JsonFactory $resultJsonFactory
     * @param CustomerOpinionFactory $customerOpinionFactory
     * @param CustomerOpinionResource $customerOpinionResource
     * @param CustomerSession $customerSession
     * @param ManagerInterface $messageManager
     * @param UrlInterface $url
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Config $config,
        protected ProductOpinionUpdater $productOpinionUpdater,
        protected JsonFactory $resultJsonFactory,
        protected CustomerOpinionFactory $customerOpinionFactory,
        protected CustomerOpinionResource $customerOpinionResource,
        protected CustomerSession $customerSession,
        protected ManagerInterface $messageManager,
        protected UrlInterface $url,
        protected RequestInterface $request,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Delete customer opinion action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        $opinionId = (int)$this->request->getParam('opinion_id');
        $myOpinionsUrl = $this->url->getUrl('opinion/index/myopinions');

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

        try {
            $customerId = (int)$this->customerSession->getCustomerId();
            $opinion = $this->customerOpinionFactory->create();
            $this->customerOpinionResource->load($opinion, $opinionId);

            if (!$opinion->getId() || (int)$opinion->getCustomerId() !== $customerId) {
                $this->messageManager->addErrorMessage(
                    __('Invalid opinion or permission denied.')
                );

                return $result->setData([
                    'success' => false
                ]);
            }

            $productId = (int)$opinion->getProductId();
            $productName = (string)$opinion->getProductName();

            $this->customerOpinionResource->delete($opinion);
            $this->productOpinionUpdater->update($productId, $productName);

            $this->messageManager->addSuccessMessage(
                __('Your opinion has been deleted successfully.')
            );

            return $result->setData([
                'success' => true,
                'redirect' => true,
                'redirect_url' => $myOpinionsUrl
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting opinion: ' . $e->getMessage());

            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting your opinion.')
            );

            return $result->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $myOpinionsUrl
            ]);
        }
    }
}
