<?php
declare(strict_types=1);

namespace Slova\Blog\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PostActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['post_id'])) {
                continue;
            }
            $id = (int)$item['post_id'];
            $item[$this->getData('name')] = [
                'edit' => [
                    'href'  => $this->urlBuilder->getUrl('slova_blog/post/edit', ['post_id' => $id]),
                    'label' => __('Editează'),
                ],
                'delete' => [
                    'href'    => $this->urlBuilder->getUrl('slova_blog/post/delete', ['post_id' => $id]),
                    'label'   => __('Șterge'),
                    'confirm' => [
                        'title'   => __('Șterge articol'),
                        'message' => __('Sigur ștergi acest articol?'),
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}
