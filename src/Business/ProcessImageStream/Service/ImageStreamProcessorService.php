<?php
declare(strict_types=1);

namespace Reifinger\Business\ProcessImageStream\Service;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class ImageStreamProcessorService
{
    private string $lastMovedImagePath = '';

    public function __construct(private readonly Filesystem $filesystem, private readonly ImageComparatorService $imageComparator, private readonly UploaderService $uploaderService)
    {
    }

    public function process(string $imageStreamFolder, string $outputFolder, float $threshold): void
    {
        if (!is_dir($imageStreamFolder)) {
            throw new RuntimeException("Source folder {$imageStreamFolder} does not exist.");
        }

        if (!is_dir($outputFolder)) {
            $this->filesystem->mkdir($outputFolder, 0755);
        }

        $this->initializeLastMovedImage($outputFolder);

        $imageFiles = glob($imageStreamFolder . '/*.JPG');
        sort($imageFiles, SORT_STRING);

        foreach ($imageFiles as $imagePath) {
            if ($this->shouldMoveImage($imagePath, $threshold)) {
                $this->moveImage($imagePath, $outputFolder);
                $this->uploaderService->upload($this->lastMovedImagePath);
            } else {
                $this->filesystem->remove($imagePath);
            }
        }
        echo PHP_EOL;
    }

    private function initializeLastMovedImage(string $outputFolder): void
    {
        $existingImages = glob($outputFolder . '/*.jpg');
        if ($existingImages) {
            rsort($existingImages, SORT_STRING);
            $this->lastMovedImagePath = $existingImages[0];
        }
    }

    private function shouldMoveImage(string $imagePath, float $threshold): bool
    {
        if (empty($this->lastMovedImagePath)) {
            return true;
        }

        $difference = $this->imageComparator->compare($this->lastMovedImagePath, $imagePath);
        echo "$imagePath: $difference" . ($difference >= $threshold ? "*" : "") . PHP_EOL;
        return $difference >= $threshold;
    }

    private function moveImage(string $sourcePath, string $outputFolder): void
    {
        $exifTimestamp = $this->getExifTimestamp($sourcePath);
        if ($exifTimestamp === null) {
            throw new \RuntimeException('Could not get EXIF timestamp for image ' . $sourcePath);
        }
        $targetFilename = $exifTimestamp->format('Y-m-d_H.i.s') . '.jpg';

        $date = $exifTimestamp->format('Y-m-d');
        $outputFolder .= '/' . $date;

        if (!is_dir($outputFolder)) {
            $this->filesystem->mkdir($outputFolder, 0755);
        }
        $targetPath = $outputFolder . '/' . $targetFilename;

        if ($this->filesystem->exists($targetPath)) {
            $this->filesystem->remove($targetPath);
        }
        $this->filesystem->rename($sourcePath, $targetPath);
        $this->lastMovedImagePath = $targetPath;
    }

    private function getExifTimestamp(string $imagePath): ?\DateTimeImmutable
    {
        $exifData = exif_read_data($imagePath, 'IFD0', true);
        if ($exifData && isset($exifData['EXIF']['DateTimeOriginal'])) {
            $originalDate = $exifData['EXIF']['DateTimeOriginal'];
            $dateTime = \DateTimeImmutable::createFromFormat('Y:m:d H:i:s', $originalDate);
            if ($dateTime) {
                return $dateTime;
            }
        }
        return null;
    }
}
