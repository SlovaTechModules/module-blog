<?php
declare(strict_types=1);

namespace Slova\Blog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory;

class Categories implements OptionSourceInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create()->applyDefaultOrder();
        foreach ($collection as $category) {
            $options[] = [
                'value' => (string)$category->getId(),
                'label' => (string)$category->getName(),
            ];
        }
        return $options;
    }
}
