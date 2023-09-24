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
            sleep(3); // Wait for the image to be captured

            $this->goProMediaService->downloadLastImage($folder);
            $this->goProMediaService->deleteLastImage();
        }

    }

    private function waitUntilNextImageCapture(int $interval): void
    {
        $now = time();
        $nextCaptureTime = $now - ($now % $interval) + $interval;
        $sleepTime = $nextCaptureTime - $now;
        sleep($sleepTime);
    }
}
