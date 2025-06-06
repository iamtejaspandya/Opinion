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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class Save implements HttpPostActionInterface
{
    /**
     * Constructor.
     *
     * @param Config $config
     * @param CustomerSession $customerSession
     * @param JsonFactory $jsonFactory
     * @param ManagerInterface $messageManager
     * @param OpinionManager $opinionManager
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param UrlInterface $url
     */
    public function __construct(
        protected Config $config,
        protected CustomerSession $customerSession,
        protected JsonFactory $jsonFactory,
        protected ManagerInterface $messageManager,
        protected OpinionManager $opinionManager,
        protected ProductRepositoryInterface $productRepository,
        protected RequestInterface $request,
        protected UrlInterface $url
    ) {
    }

    /**
     * Save customer opinion action.
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

        $data = $this->request->getPostValue();
        $productId = (int)($data['product_id'] ?? 0);
        $newOpinion = isset($data['opinion']) ? (int)$data['opinion'] : null;

        if (empty($productId) || !in_array($newOpinion, [0, 1], true)) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid data provided.')
            ]);
        }

        $customerId = (int)$this->customerSession->getCustomerId();

        $saveResult = $this->opinionManager->customerOpinionSave(
            $customerId,
            $productId,
            $newOpinion
        );

        $isMyOpinionsPage = str_contains($refererUrl, 'opinion/index/myopinions');

        if ($saveResult['success']) {
            $message = $saveResult['message'];

            if ($isMyOpinionsPage) {
                if ($saveResult['opinion'] !== null &&
                    ($this->config->isOpinionChartEnabled() || $this->config->isCurrentOpinionChartEnabled())
                ) {
                    $message .= ' ' . __('Please refresh the page to see updated chart(s).');
                }

                $this->messageManager->addSuccessMessage($message);
            }
        } else {
            if ($isMyOpinionsPage) {
                $this->messageManager->addErrorMessage($saveResult['message']);
            }
        }

        return $result->setData($saveResult);
    }
}
