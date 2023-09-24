<?php
declare(strict_types=1);

namespace Reifinger\Business\CaptureImages\Service;

use Reifinger\Infrastructure\GoProAccess\Service\GoProControlService;
use Reifinger\Infrastructure\GoProAccess\Service\GoProMediaService;

readonly class ImageCaptureService
{
    public function __construct(
            private GoProControlService $goProControlService,
            private GoProMediaService   $goProMediaService
    )
    {
    }

    public function captureImages(string $folder, int $interval): int
    {
        $this->goProControlService->setupGoPro();

        while (true) {
            $this->waitUntilNextImageCapture($interval);
            $this->goProControlService->triggerPhoto();
            $this->downloadAllImages($folder);
        }

    }

    private function waitUntilNextImageCapture(int $interval): void
    {
        $now = time();
        $nextCaptureTime = $now - ($now % $interval) + $interval;
        $sleepTime = $nextCaptureTime - $now;
        sleep($sleepTime);
    }

    private function downloadAllImages(string $folder)
    {
        try {
            $imageUrls = $this->goProMediaService->getImageUrls();

            if (empty($imageUrls)) {
                return;
            }

            foreach ($imageUrls as $url) {
                $filename = basename($url);
                $filepath = $folder . '/' . $filename;

                $this->goProMediaService->downloadImage($url, $filepath);
            }

            $this->goProMediaService->deleteAllImages();
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }

    }
}
