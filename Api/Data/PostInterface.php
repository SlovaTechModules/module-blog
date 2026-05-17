<?php
declare(strict_types=1);

namespace Slova\Blog\Api\Data;

interface PostInterface
{
    public const POST_ID = 'post_id';
    public const TITLE = 'title';
    public const URL_KEY = 'url_key';
    public const SHORT_CONTENT = 'short_content';
    public const CONTENT = 'content';
    public const FEATURED_IMAGE = 'featured_image';
    public const FEATURED_IMAGE_ALT = 'featured_image_alt';
    public const IS_ACTIVE = 'is_active';
    public const PUBLISHED_AT = 'published_at';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const META_ROBOTS = 'meta_robots';
    public const OG_IMAGE = 'og_image';
    public const SHOW_FEATURED_IMAGE = 'show_featured_image';
    public const SIDE_TEXT = 'side_text';
    public const SIDE_IMAGE = 'side_image';
    public const SIDE_IMAGE_ALT = 'side_image_alt';

    public const CACHE_TAG = 'slova_blog_post';
    public const ENTITY_TYPE = 'slova_blog_post';
}
