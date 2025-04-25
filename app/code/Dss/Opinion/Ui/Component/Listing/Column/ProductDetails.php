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

namespace Dss\Opinion\Ui\Component\Listing\Column;

use Dss\Opinion\Model\ResourceModel\Opinion\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ProductDetails extends Column
{
    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $opinionCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected ProductRepositoryInterface $productRepository,
        protected CollectionFactory $opinionCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    /**
     * Prepares the data source for the listing column
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $collection = $this->opinionCollectionFactory->create();
            $opinionData = [];

            foreach ($collection as $item) {
                $productId = (int)$item['product_id'];
                $product = $this->productRepository->getById($productId);

                $opinionData[$productId] = [
                    'product_name' => $product->getName(),
                    'total_like_opinion_count' => $collection->getLikeCount($productId),
                    'total_dislike_opinion_count' => $collection->getDislikeCount($productId)
                ];
            }

            foreach ($dataSource['data']['items'] as &$item) {
                $productId = (int)$item['product_id'];
                if (isset($opinionData[$productId])) {
                    $item['product_name'] = $opinionData[$productId]['product_name'];
                    $item['total_like_opinion_count'] = $opinionData[$productId]['total_like_opinion_count'];
                    $item['total_dislike_opinion_count'] = $opinionData[$productId]['total_dislike_opinion_count'];
                }
            }
        }

        return $dataSource;
    }
}
