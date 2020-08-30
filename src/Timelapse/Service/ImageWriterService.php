<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Imagick;
use RuntimeException;

class ImageWriterService
{
    private int $imageNr;
    private FileService $fileService;
    private PathService $imagePathService;

    public function __construct(FileService $fileService, PathService $imagePathService)
    {
        $this->fileService = $fileService;
        $this->imagePathService = $imagePathService;
    }

    public function writeImage(Imagick $targetImage): void
    {
        $imagePath = $this->imagePathService->getTmpPath().'/'.str_pad(''.$this->imageNr, 8, '0', STR_PAD_LEFT).'.png';
        $targetImage->writeImage($imagePath);
        $this->imageNr++;
    }

    public function initializeOutputDirectory(bool $force): void
    {
        $this->imageNr = 1;
        $targetPath = $this->imagePathService->getOutputPath();
        if (is_dir($targetPath)) {
            if ($force) {
                $this->fileService->removeDirectoryRecursive($targetPath);
            } else {
                throw new RuntimeException(
                        'Target folder '.$targetPath.' exists, please use --force to delete it or remove it manually before restarting.'
                );
            }
        }
        $tmpPath = $this->imagePathService->getTmpPath();
        if (!mkdir($tmpPath, 0777, true) && !is_dir($tmpPath)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $targetPath));
        }
    }
}
