<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Moves an image uploaded in the post form from pub/media/slova/blog/tmp to pub/media/slova/blog.
 */
class ImageUploader
{
    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param string $basePath
     * @param string $baseTmpPath
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
        private readonly string $basePath = 'slova/blog',
        private readonly string $baseTmpPath = 'slova/blog/tmp'
    ) {
    }

    /**
     * Move the file and return the name it is stored under.
     *
     * A different file with the same name may already be there (another post's image):
     * it is never overwritten; the new file gets a free name (photo_1.jpg, photo_2.jpg…).
     * When the tmp file is gone (moved by an earlier save or preview), the name is
     * returned unchanged.
     *
     * @param string $imageName
     * @return string
     * @throws LocalizedException
     */
    public function moveFileFromTmp(string $imageName): string
    {
        $imageName = basename($imageName);
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $tmpFilePath = $this->baseTmpPath . '/' . $imageName;
        if (!$media->isExist($tmpFilePath)) {
            return $imageName;
        }

        try {
            $finalName = $imageName;
            $info = pathinfo($imageName);
            $index = 1;
            while ($media->isExist($this->basePath . '/' . $finalName)) {
                if ($this->isSameFile($media->getAbsolutePath($tmpFilePath), $media->getAbsolutePath($this->basePath . '/' . $finalName))) {
                    $media->delete($tmpFilePath);
                    return $finalName;
                }
                $finalName = $info['filename'] . '_' . $index++ . (isset($info['extension']) ? '.' . $info['extension'] : '');
            }
            $media->renameFile($tmpFilePath, $this->basePath . '/' . $finalName);
            return $finalName;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(__('Could not move uploaded image.'));
        }
    }

    /**
     * Whether two files have the same content.
     *
     * @param string $fileA
     * @param string $fileB
     * @return bool
     */
    private function isSameFile(string $fileA, string $fileB): bool
    {
        return filesize($fileA) === filesize($fileB) && md5_file($fileA) === md5_file($fileB);
    }
}
