<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Sidebar;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

class TopProducts extends Template
{
    public const DEFAULT_LIMIT = 5;

    /** @var Product[]|null */
    private ?array $products = null;

    public function __construct(
        Template\Context $context,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getLimit(): int
    {
        return (int)($this->getData('limit') ?: self::DEFAULT_LIMIT);
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        if ($this->products === null) {
            $ids = $this->getTopProductIds();
            if (!$ids) {
                $this->products = [];
                return $this->products;
            }
            $collection = $this->productCollectionFactory->create();
            $collection->addIdFilter($ids)
                ->addAttributeToSelect(['name', 'price', 'special_price', 'thumbnail', 'small_image', 'url_key', 'image'])
                ->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED)
                ->addAttributeToFilter('visibility', ['in' => [
                    ProductVisibility::VISIBILITY_IN_CATALOG,
                    ProductVisibility::VISIBILITY_BOTH,
                ]])
                ->addUrlRewrite()
                ->addFinalPrice();

            $byId = [];
            foreach ($collection as $product) {
                $byId[(int)$product->getId()] = $product;
            }
            $ordered = [];
            foreach ($ids as $id) {
                if (isset($byId[$id])) {
                    $ordered[] = $byId[$id];
                }
            }
            $this->products = $ordered;
        }
        return $this->products;
    }

    /**
     * @return int[]
     */
    private function getTopProductIds(): array
    {
        $connection = $this->resource->getConnection();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $select = $connection->select()
            ->from(['s' => $this->resource->getTableName('sales_bestsellers_aggregated_monthly')], ['product_id'])
            ->where('s.store_id = ?', $storeId)
            ->order('s.qty_ordered DESC')
            ->order('s.rating_pos ASC')
            ->limit($this->getLimit());
        $ids = array_map('intval', $connection->fetchCol($select));

        if (!$ids) {
            $select = $connection->select()
                ->from(['s' => $this->resource->getTableName('sales_bestsellers_aggregated_yearly')], ['product_id'])
                ->where('s.store_id = ?', $storeId)
                ->order('s.qty_ordered DESC')
                ->limit($this->getLimit());
            $ids = array_map('intval', $connection->fetchCol($select));
        }
        return array_values(array_unique($ids));
    }
}
