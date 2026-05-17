<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class Category extends Action
{
    public const ADMIN_RESOURCE = 'Slova_Blog::category';
}
