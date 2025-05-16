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
use Dss\Opinion\Model\OpinionFactory;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion as CustomerOpinionResource;
use Dss\Opinion\Model\Service\ProductOpinionUpdater;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Save implements HttpPostActionInterface
{
    /**
     * Constructor.
     *
     * @param Config $config
     * @param ProductOpinionUpdater $productOpinionUpdater
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerSession $customerSession
     * @param CustomerOpinionFactory $customerProductOpinionFactory
     * @param OpinionFactory $productOpinionFactory
     * @param CustomerOpinionResource $customerOpinionResource
     * @param JsonFactory $jsonFactory
     * @param UrlInterface $url
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Config $config,
        protected ProductOpinionUpdater $productOpinionUpdater,
        protected ProductRepositoryInterface $productRepository,
        protected CustomerSession $customerSession,
        protected CustomerOpinionFactory $customerProductOpinionFactory,
        protected OpinionFactory $productOpinionFactory,
        protected CustomerOpinionResource $customerOpinionResource,
        protected JsonFactory $jsonFactory,
        protected UrlInterface $url,
        protected RequestInterface $request,
        protected ManagerInterface $messageManager,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Save customer opinion action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->jsonFactory->create();
        $data = $this->request->getPostValue();
        $productId = (int)($data['product_id'] ?? 0);

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(
                __('Looks like you\'re logged out. Please sign in to share your thoughts!')
            );
            return $resultJson->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $this->url->getUrl('customer/account/login')
            ]);
        }

        if (!$this->config->isProductOpinionEnabled()) {
            $this->messageManager->addErrorMessage(
                __('The product opinion feature is currently disabled.')
            );
            return $resultJson->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $this->getRedirectUrl($productId)
            ]);
        }

        if (!$this->config->isOpinionSubmissionAllowed()) {
            $this->messageManager->addErrorMessage(
                __('We appreciate your opinion! However, submissions are currently turned off.')
            );
            return $resultJson->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $this->getRedirectUrl($productId)
            ]);
        }

        if (!$this->config->canCustomerGiveOpinion()) {
            $this->messageManager->addErrorMessage(
                __('Your account is currently restricted from submitting opinions.')
            );
            return $resultJson->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $this->getRedirectUrl($productId)
            ]);
        }

        if (empty($data['product_id']) || !isset($data['opinion'])) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid data provided.')
            ]);
        }

        try {
            $customerId = (int)$this->customerSession->getCustomerId();
            $productName = $data['product_name'] ?? '';
            $newOpinion = (int)$data['opinion'];

            $opinion = $this->customerProductOpinionFactory->create();
            $this->customerOpinionResource->loadByCustomerAndProduct(
                $opinion,
                $customerId,
                $productId
            );

            if ($opinion->getId()) {
                if ($opinion->getOpinion() === $newOpinion) {
                    $this->messageManager->addSuccessMessage(
                        __('Your opinion is already submitted.')
                    );
                    return $resultJson->setData([
                        'success' => true,
                        'message' => __('Your opinion is already submitted.')
                    ]);
                }

                $opinion->setOpinion($newOpinion);
                $this->customerOpinionResource->save($opinion);
                $this->productOpinionUpdater->update($productId, $productName);

                $referer = $this->request->getServer('HTTP_REFERER') ?? '';

                if (str_contains($referer, 'opinion/index/myopinions')) {
                    if ($this->config->isOpinionChartEnabled() || $this->config->isCurrentOpinionChartEnabled()) {
                        $this->messageManager->addSuccessMessage(__(
                            'Your opinion has been updated successfully. ' .
                            'Please refresh the page to see updated charts.'
                        ));
                    } else {
                        $this->messageManager->addSuccessMessage(
                            __('Your opinion has been updated successfully.')
                        );
                    }
                }

                return $resultJson->setData([
                    'success' => true,
                    'message' => __('Your opinion has been updated successfully.'),
                    'opinion' => $newOpinion
                ]);
            }

            $opinion->setData([
                'customer_id' => $customerId,
                'product_id' => $productId,
                'product_name' => $productName,
                'opinion' => $newOpinion
            ]);

            $this->customerOpinionResource->save($opinion);
            $this->productOpinionUpdater->update($productId, $productName);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Your opinion has been submitted successfully.'),
                'opinion' => $newOpinion
            ]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $resultJson->setData([
                'success' => false,
                'message' => __('Something went wrong while saving your opinion.')
            ]);
        }
    }

    /**
     * Get product URL or fallback to My Opinions page if accessed from there.
     *
     * @param int $productId
     * @return string
     */
    private function getRedirectUrl(int $productId): string
    {
        $referer = $this->request->getServer('HTTP_REFERER') ?? '';
        $myOpinionsUrl = $this->url->getUrl('opinion/index/myopinions');

        if (str_contains($referer, 'opinion/index/myopinions')) {
            return $myOpinionsUrl;
        }

        return $this->productRepository->getById($productId)->getProductUrl();
    }
}
