<?php
declare(strict_types=1);

namespace Slova\Blog\Api\Data;

interface CategoryInterface
{
    public const CATEGORY_ID = 'category_id';
    public const NAME = 'name';
    public const URL_KEY = 'url_key';
    public const IS_ACTIVE = 'is_active';
    public const SORT_ORDER = 'sort_order';

    public const CACHE_TAG = 'slova_blog_category';
    public const ENTITY_TYPE = 'slova_blog_category';
}
