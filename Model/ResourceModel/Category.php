<?php
declare(strict_types=1);

namespace Slova\Blog\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Slova\Blog\Model\UrlRewriteGenerator;

class Category extends AbstractDb
{
    public function __construct(
        Context $context,
        private readonly UrlRewriteGenerator $urlRewriteGenerator,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    protected function _construct(): void
    {
        $this->_init('slova_blog_category', 'category_id');
    }

    protected function _afterSave(AbstractModel $object)
    {
        $result = parent::_afterSave($object);
        if ($object->getUrlKey()) {
            $this->urlRewriteGenerator->generateForCategory(
                (int)$object->getId(),
                (string)$object->getUrlKey()
            );
        }
        return $result;
    }

    protected function _afterDelete(AbstractModel $object)
    {
        $this->urlRewriteGenerator->deleteForCategory((int)$object->getId());
        return parent::_afterDelete($object);
    }

    public function getPostCount(int $categoryId): int
    {
        $connection = $this->getConnection();
        return (int)$connection->fetchOne(
            $connection->select()
                ->from(['cat_link' => $this->getTable('slova_blog_post_category')], ['cnt' => 'COUNT(*)'])
                ->join(
                    ['post' => $this->getTable('slova_blog_post')],
                    'post.post_id = cat_link.post_id AND post.is_active = 1',
                    []
                )
                ->where('cat_link.category_id = ?', $categoryId)
        );
    }
}
