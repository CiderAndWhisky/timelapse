<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Imagick;
use RuntimeException;

class ImageWriterService
{
    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function writeImage(Imagick $targetImage, int $frameNr, string $targetPath): void
    {
        $imagePath = $targetPath.'/'.str_pad(''.$frameNr, 8, '0', STR_PAD_LEFT).'.png';
        $targetImage->writeImage($imagePath);
    }

    public function initializeOutputDirectory(string $targetPath, string $tmpPath, bool $force): void
    {
        if (is_dir($targetPath)) {
            if ($force) {
                $this->fileService->removeDirectoryRecursive($targetPath);
            } else {
                throw new RuntimeException(
                        'Target folder '.$targetPath.' exists, please use --force to delete it or remove it manually before restarting.'
                );
            }
        }
        if (!mkdir($tmpPath, 0777, true) && !is_dir($tmpPath)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $targetPath));
        }
    }
}
