<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * `module:uninstall Slova_Blog --remove-data`: drops the blog tables and deletes the
 * blog's URL rewrites and settings. Uploaded images in pub/media/slova/blog and the
 * resized copies in pub/media/slova_blog_resized are left for you to delete.
 */
class Uninstall implements UninstallInterface
{
    private const TABLES = [
        'slova_blog_post_product',
        'slova_blog_post_category',
        'slova_blog_post',
        'slova_blog_category',
    ];

    private const ENTITY_TYPES = ['slova_blog_post', 'slova_blog_category', 'slova_blog_index'];

    /**
     * Remove the module's data.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        $connection->delete(
            $setup->getTable('url_rewrite'),
            ['entity_type IN (?)' => self::ENTITY_TYPES]
        );
        $connection->delete(
            $setup->getTable('core_config_data'),
            ['path LIKE ?' => 'slova_blog/%']
        );
        foreach (self::TABLES as $table) {
            $connection->dropTable($setup->getTable($table));
        }

        $setup->endSetup();
    }
}
