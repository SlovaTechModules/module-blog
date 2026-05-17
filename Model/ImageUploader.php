<?php
declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

class ImageUploader
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
        private readonly string $basePath = 'slova/blog',
        private readonly string $baseTmpPath = 'slova/blog/tmp'
    ) {
    }

    public function moveFileFromTmp(string $imageName): string
    {
        $baseDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $tmpFilePath = $this->baseTmpPath . '/' . $imageName;
        $filePath = $this->basePath . '/' . $imageName;

        try {
            if ($baseDirectory->isExist($tmpFilePath)) {
                $baseDirectory->copyFile($tmpFilePath, $filePath);
                $baseDirectory->delete($tmpFilePath);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(__('Could not move uploaded image.'));
        }

        return $imageName;
    }
}
