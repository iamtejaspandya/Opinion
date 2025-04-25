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
     * @param Config $Config
     * @param JsonFactory $jsonFactory
     * @param OpinionFactory $productOpinionFactory
     * @param CustomerOpinionFactory $customerOpinionFactory
     * @param Session $customerSession
     * @param RequestInterface $request
     */
    public function __construct(
        protected Config $Config,
        protected JsonFactory $jsonFactory,
        protected OpinionFactory $productOpinionFactory,
        protected CustomerOpinionFactory $customerOpinionFactory,
        protected Session $customerSession,
        protected RequestInterface $request
    ) {
    }

    /**
     * Execute method to fetch personalized product opinion label.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();
        $productId = (int) $this->request->getParam('product_id');

        if (!$productId) {
            return $resultJson->setData(['error' => true, 'message' => __('Invalid product.'), 'class' => 'error']);
        }

        $opinion = $this->productOpinionFactory->create()->load($productId, 'product_id');

        $totalLikes = (int) $opinion->getTotalLikeOpinionCount();
        $totalDislikes = (int) $opinion->getTotalDislikeOpinionCount();
        $totalOpinions = $totalLikes + $totalDislikes;

        $customerOpinion = null;
        if ($this->customerSession->isLoggedIn()) {
            $customerId = (int) $this->customerSession->getCustomerId();
            $customerOpinion = $this->customerOpinionFactory->create()
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('product_id', $productId)
                ->getFirstItem()
                ->getOpinion();
        }

        $percentage = $totalOpinions ? round(($totalLikes / $totalOpinions) * 100) : 0;
        $minThreshold = $this->Config->getMinimumOpinionThreshold();
        $minLike = $this->Config->getMinimumLikePercentage();
        $message = '';
        $class = '';

        if ($totalOpinions === 0) {
            $message = __('Be the first to share your opinion!');
            $class = 'no-opinion';
        } elseif ($totalOpinions === 1) {
            $message = $customerOpinion !== null
                ? ($customerOpinion ? __('First opinion in — and it’s a thumbs-up!')
                                    : __('First opinion in — not your favorite.'))
                : __('One opinion in! Share yours!');
            $class = 'one-opinion';
        } elseif ($totalOpinions < $minThreshold) {
            if ($totalLikes > 0 && $totalDislikes === 0) {
                if ($customerOpinion !== null && $customerOpinion) {
                    $message = __('You liked this—waiting for more opinions!');
                } else {
                    $message = __('Liked by some of our customers.');
                }
                $class = 'someliked';
            } elseif ($totalLikes === 0 && $totalDislikes > 0) {
                if ($customerOpinion !== null && !$customerOpinion) {
                    $message = __('Not your pick! Others haven’t shared yet.');
                } else {
                    $message = __('More opinions needed! What do you think?');
                }
                $class = 'not-enough';
            } else {
                $message = $customerOpinion !== null
                    ? ($customerOpinion ? __('This product got your like!, but opinions are mixed.')
                                        : __('Not your favorite, but opinions are mixed.'))
                    : __('This product has received mixed opinions.');
                $class = 'mixed';
            }
        } else {
            if ($percentage >= $minLike) {
                $message = $customerOpinion !== null
                    ? ($customerOpinion ? __(
                        'You and %1% of our %2 customers liked this product',
                        $percentage,
                        $totalOpinions
                    )
                        : __(
                            'Not your favorite, but %1% of our %2 customers liked it',
                            $percentage,
                            $totalOpinions
                        ))
                    : __('%1% of our %2 customers liked this product', $percentage, $totalOpinions);
                $class = 'liked';
            } else {
                $message = $customerOpinion !== null
                    ? ($customerOpinion ? __('This product got your like!, but opinions are mixed.')
                                        : __('Not your favorite, but opinions are mixed.'))
                    : __('This product has received mixed opinions.');
                $class = 'mixed';
            }
        }

        return $resultJson->setData([
            'success' => true,
            'percentage' => $percentage,
            'total_opinions' => $totalOpinions,
            'message' => $message,
            'class' => $class
        ]);
    }
}
