<?php
declare(strict_types=1);

namespace Reifinger\Business\ImageStreamTimelapse\Service;

use DateTimeImmutable;
use Imagick;
use Reifinger\Business\ImageStreamTimelapse\Event\EventInterface;

readonly class TimeLapseCreatorService
{
    public function __construct(private EventInterface $events)
    {
    }

    public function createTimelapse(
            string $inputFolder,
            string $outputFolder,
            float  $speedUpFactor
    ): void
    {
        $inputFiles = array_filter(scandir($inputFolder), function ($filename) {
            return preg_match('/\.jpg$/i', $filename);
        });
        sort($inputFiles); // Sort by filename

        $firstTime = DateTimeImmutable::createFromFormat('Y-m-d_H.i.s', substr($inputFiles[0], 0, 19));
        $lastTime = DateTimeImmutable::createFromFormat('Y-m-d_H.i.s', substr(end($inputFiles), 0, 19));

        $totalSeconds = $lastTime->getTimestamp() - $firstTime->getTimestamp();
        $totalFrames = (int)floor(($totalSeconds / $speedUpFactor) * 30); // Assuming 30 FPS

        if (!is_dir($outputFolder)) {
            if (!mkdir($outputFolder, 0777, true) && !is_dir($outputFolder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $outputFolder));
            }
        }

        $this->events->reportMeta($totalSeconds, $totalFrames, (int)($totalSeconds / $speedUpFactor));
        for ($i = 0; $i < $totalFrames; ++$i) {
            $frameTime = $firstTime->modify('+' . floor(($i * $speedUpFactor) / 30) . ' seconds');
            [$beforeImage, $afterImage, $ratio] = $this->findClosestImages($inputFiles, $frameTime);

            $image1 = new Imagick($inputFolder . '/' . $beforeImage);
            $image2 = new Imagick($inputFolder . '/' . $afterImage);

            $outputImage = $this->fadeBetweenImages($image1, $image2, $ratio);

            $outputFilename = sprintf('%s/frame_%04d.jpg', $outputFolder, $i);
            $outputImage->writeImage($outputFilename);
            $this->events->reportProgress();
        }
    }

    private function findClosestImages(array $inputFiles, DateTimeImmutable $frameTime): array
    {
        $closestBefore = null;
        $closestAfter = null;
        $closestBeforeTime = null;
        $closestAfterTime = null;

        foreach ($inputFiles as $filename) {
            $time = DateTimeImmutable::createFromFormat('Y-m-d_H.i.s', substr($filename, 0, 19));
            if ($time <= $frameTime) {
                $closestBefore = $filename;
                $closestBeforeTime = $time;
            } else {
                $closestAfter = $filename;
                $closestAfterTime = $time;
                break;
            }
        }

        $beforeDiff = $frameTime->getTimestamp() - $closestBeforeTime->getTimestamp();
        $afterDiff = $closestAfterTime->getTimestamp() - $frameTime->getTimestamp();
        $totalDiff = $beforeDiff + $afterDiff;

        $ratio = $beforeDiff / $totalDiff;

        return [$closestBefore, $closestAfter, $ratio];
    }

    private function fadeBetweenImages(Imagick $image1, Imagick $image2, float $ratio): Imagick
    {
        $image1->evaluateImage(Imagick::EVALUATE_MULTIPLY, 1 - $ratio, Imagick::CHANNEL_ALL);
        $image2->evaluateImage(Imagick::EVALUATE_MULTIPLY, $ratio, Imagick::CHANNEL_ALL);

        $image1->compositeImage($image2, Imagick::COMPOSITE_BLEND, 0, 0);
        return $image1;
    }
}
