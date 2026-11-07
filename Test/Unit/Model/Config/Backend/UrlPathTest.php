<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Test\Unit\Model\Config\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Slova\Blog\Model\Config\Backend\Color;
use Slova\Blog\Model\Config\Backend\UrlPath;
use Slova\Blog\Model\UrlKeyGenerator;

class UrlPathTest extends TestCase
{
    public function testPrefixIsNormalised(): void
    {
        $model = (new ObjectManager($this))->getObject(UrlPath::class, ['urlKeyGenerator' => new UrlKeyGenerator()]);
        $model->setValue(' News/Știri ');
        $model->beforeSave();
        $this->assertSame('news/stiri', $model->getValue());
    }

    public function testUnusablePrefixIsRefused(): void
    {
        $model = (new ObjectManager($this))->getObject(UrlPath::class, ['urlKeyGenerator' => new UrlKeyGenerator()]);
        $model->setValue('!!!');
        $this->expectException(LocalizedException::class);
        $model->beforeSave();
    }

    public function testColor(): void
    {
        $om = new ObjectManager($this);
        foreach (['', '#abc', '#0F766E'] as $ok) {
            $model = $om->getObject(Color::class);
            $model->setValue($ok);
            $model->beforeSave();
            $this->assertSame($ok, $model->getValue());
        }
        $model = $om->getObject(Color::class);
        $model->setValue('red;}body{display:none');
        $this->expectException(LocalizedException::class);
        $model->beforeSave();
    }
}
