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
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Suggest implements HttpGetActionInterface
{
    /**
     * Constructor.
     *
     * @param OpinionManager $opinionManager
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     */
    public function __construct(
        protected OpinionManager $opinionManager,
        protected JsonFactory $resultJsonFactory,
        protected RequestInterface $request
    ) {
    }

    /**
     * Executes the suggestion endpoint
     *
     * @return Json
     */
    public function execute(): Json
    {
        $query = $this->request->getParam('q');
        $result = $this->resultJsonFactory->create();

        if (!$query || mb_strlen(trim($query)) < 3) {
            return $result->setData([]);
        }

        $collection = $this->opinionManager->getFilteredProductCollection(trim($query));

        $products = [];
        foreach ($collection as $product) {
            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
            ];
        }

        return $result->setData($products);
    }
}
