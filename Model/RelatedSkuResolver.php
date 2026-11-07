<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Turns the "Related Products (SKUs)" field into product ids, in the order entered.
 */
class RelatedSkuResolver
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * Resolve SKUs separated by commas or white space.
     *
     * @param string $skuList
     * @return array{ids:int[],missing:string[]}
     */
    public function resolve(string $skuList): array
    {
        $skus = array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', trim($skuList)) ?: [])));
        if (!$skus) {
            return ['ids' => [], 'missing' => []];
        }

        $criteria = $this->searchCriteriaBuilder->addFilter('sku', $skus, 'in')->create();
        $idsBySku = [];
        foreach ($this->productRepository->getList($criteria)->getItems() as $product) {
            $idsBySku[strtolower((string)$product->getSku())] = (int)$product->getId();
        }

        $ids = [];
        $missing = [];
        foreach ($skus as $sku) {
            $key = strtolower($sku);
            if (isset($idsBySku[$key])) {
                $ids[] = $idsBySku[$key];
            } else {
                $missing[] = $sku;
            }
        }
        return ['ids' => array_values(array_unique($ids)), 'missing' => $missing];
    }
}
