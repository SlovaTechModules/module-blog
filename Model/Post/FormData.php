<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Post;

use Slova\Blog\Model\ImageUploader;
use Slova\Blog\Model\RelatedSkuResolver;
use Slova\Blog\Model\UrlKeyGenerator;

/**
 * Turns the values of the admin post form into post data: uploaded images moved out of
 * tmp, URL key, publish date, categories, related products. Used by Save, Save Draft
 * and Preview, so a preview shows exactly what a save would store.
 */
class FormData
{
    public const IMAGE_FIELDS = ['featured_image', 'thumbnail_mobile', 'side_image', 'og_image'];

    /**
     * @param ImageUploader $imageUploader
     * @param UrlKeyGenerator $urlKeyGenerator
     * @param RelatedSkuResolver $relatedSkuResolver
     */
    public function __construct(
        private readonly ImageUploader $imageUploader,
        private readonly UrlKeyGenerator $urlKeyGenerator,
        private readonly RelatedSkuResolver $relatedSkuResolver
    ) {
    }

    /**
     * Normalize form values.
     *
     * @param array $data
     * @return array{data:array,missing_skus:string[]}
     */
    public function normalize(array $data): array
    {
        foreach (self::IMAGE_FIELDS as $field) {
            $data[$field] = $this->normalizeImage($data[$field] ?? null);
        }

        $source = !empty($data['url_key']) ? (string)$data['url_key'] : (string)($data['title'] ?? '');
        $data['url_key'] = $this->urlKeyGenerator->generate($source);

        if (empty($data['published_at'])) {
            $data['published_at'] = null;
        }

        $categoryIds = $data['category_ids'] ?? [];
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }
        $data['category_ids'] = array_values(array_filter(array_map('intval', (array)$categoryIds)));

        $related = $this->relatedSkuResolver->resolve((string)($data['related_skus'] ?? ''));
        unset($data['related_skus']);
        $data['product_ids'] = $related['ids'];

        if (empty($data['post_id'])) {
            unset($data['post_id']);
        }

        return ['data' => $data, 'missing_skus' => $related['missing']];
    }

    /**
     * Image uploader value → stored file name (new uploads are moved out of tmp), or null.
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizeImage(mixed $value): ?string
    {
        if (is_array($value)) {
            $item = $value[0] ?? null;
            $name = is_array($item) ? (string)($item['file'] ?? $item['name'] ?? '') : '';
            if ($name === '') {
                return null;
            }
            // A path ("folder/x.jpg") was picked from the media gallery: keep it as it is.
            return str_contains($name, '/') ? ltrim($name, '/') : $this->imageUploader->moveFileFromTmp($name);
        }
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }
}
