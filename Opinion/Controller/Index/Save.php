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

use Dss\Opinion\Model\CustomerOpinionFactory;
use Dss\Opinion\Model\OpinionFactory;
use Dss\Opinion\Model\ResourceModel\CustomerOpinion as CustomerOpinionResource;
use Dss\Opinion\Model\ResourceModel\Opinion as ProductOpinionResource;
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
     * @param CustomerSession $customerSession
     * @param CustomerOpinionFactory $customerProductOpinionFactory
     * @param OpinionFactory $productOpinionFactory
     * @param CustomerOpinionResource $customerOpinionResource
     * @param ProductOpinionResource $productOpinionResource
     * @param JsonFactory $jsonFactory
     * @param UrlInterface $url
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CustomerSession $customerSession,
        protected CustomerOpinionFactory $customerProductOpinionFactory,
        protected OpinionFactory $productOpinionFactory,
        protected CustomerOpinionResource $customerOpinionResource,
        protected ProductOpinionResource $productOpinionResource,
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

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired. Please log in again to submit your opinion.')
            );
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'redirect' => true,
                'redirect_url' => $this->url->getUrl('customer/account/login')
            ]);
        }

        $data = $this->request->getPostValue();

        if (!empty($data['product_id']) && isset($data['opinion'])) {
            try {
                $customerId = (int)$this->customerSession->getCustomerId();
                $productId = (int)$data['product_id'];
                $productName = $data['product_name'] ?? '';

                $opinion = $this->customerProductOpinionFactory->create();
                $this->customerOpinionResource->loadByCustomerAndProduct($opinion, $customerId, $productId);

                if ($opinion->getId()) {
                    if ($opinion->getOpinion() !== (int)$data['opinion']) {
                        $opinion->setOpinion((int)$data['opinion']);
                        $this->customerOpinionResource->save($opinion);

                        $this->updateProductOpinionCounts($productId, $productName);

                        return $resultJson->setData([
                            'success' => true,
                            'message' => __('Your opinion has been updated successfully.'),
                            'opinion' => (int)$data['opinion']
                        ]);
                    }

                    return $resultJson->setData([
                        'success' => true,
                        'message' => __('Your opinion is already submitted.')
                    ]);
                }

                $opinion->setData([
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'opinion' => (int)$data['opinion']
                ]);
                $this->customerOpinionResource->save($opinion);

                $this->updateProductOpinionCounts($productId, $productName);

                return $resultJson->setData([
                    'success' => true,
                    'message' => __('Your opinion has been submitted successfully.'),
                    'opinion' => (int)$data['opinion']
                ]);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Something went wrong while saving your opinion.')
                ]);
            }
        }

        return $resultJson->setData([
            'success' => false,
            'message' => __('Invalid data provided.')
        ]);
    }

    /**
     * Update total like and dislike counts for the product.
     *
     * @param int $productId
     * @param string $productName
     * @return void
     */
    private function updateProductOpinionCounts(int $productId, string $productName): void
    {
        try {
            $connection = $this->customerOpinionResource->getConnection();
            $tableName = $this->customerOpinionResource->getMainTable();

            $select = $connection->select()
                ->from($tableName, [
                    'total_likes' => 'SUM(opinion = 1)',
                    'total_dislikes' => 'SUM(opinion = 0)'
                ])
                ->where('product_id = ?', $productId);

            $result = $connection->fetchRow($select);

            $totalLikes = (int)($result['total_likes'] ?? 0);
            $totalDislikes = (int)($result['total_dislikes'] ?? 0);

            $productOpinion = $this->productOpinionFactory->create();
            $this->productOpinionResource->load($productOpinion, $productId, 'product_id');

            if (!$productOpinion->getId()) {
                $productOpinion->setData([
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'total_like_opinion_count' => $totalLikes,
                    'total_dislike_opinion_count' => $totalDislikes
                ]);
            } else {
                $productOpinion->setTotalLikeOpinionCount($totalLikes);
                $productOpinion->setTotalDislikeOpinionCount($totalDislikes);
            }

            $this->productOpinionResource->save($productOpinion);
        } catch (\Exception $e) {
            $this->logger->error('Error updating product opinion counts: ' . $e->getMessage());
        }
    }
}
