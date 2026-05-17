<?php
declare(strict_types=1);

namespace Slova\Blog\Model\Category;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        if (!$items) {
            $this->loadedData[''] = [
                'is_active'  => 1,
                'sort_order' => 0,
            ];
        }

        foreach ($items as $category) {
            $this->loadedData[(int)$category->getId()] = $category->getData();
        }

        $persisted = $this->dataPersistor->get('slova_blog_category');
        if (!empty($persisted)) {
            $category = $this->collection->getNewEmptyItem();
            $category->setData($persisted);
            $this->loadedData[(int)$category->getId()] = $category->getData();
            $this->dataPersistor->clear('slova_blog_category');
        }

        return $this->loadedData;
    }
}
