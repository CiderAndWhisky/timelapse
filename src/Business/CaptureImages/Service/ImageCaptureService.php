<?php
declare(strict_types=1);

namespace Reifinger\Business\CaptureImages\Service;

use Reifinger\Infrastructure\GoProAccess\Enum\BatteryStateEnum;
use Reifinger\Infrastructure\GoProAccess\Service\GoProControlService;
use Reifinger\Infrastructure\GoProAccess\Service\GoProMediaService;
use Throwable;

readonly class ImageCaptureService
{
    private const NORMAL_PHOTO_MODE = 1;
    private const NIGHT_PHOTO_MODE = 2;

    public function __construct(
            private GoProControlService $goProControlService,
            private GoProMediaService   $goProMediaService
    )
    {
    }

    public function captureImages(string $folder, int $interval): int
    {
        $lastPhotoMode = $this->determineMode();
        $this->goProControlService->setupGoPro($lastPhotoMode);
        while (true) {
            $photoMode = $this->determineMode();
            if ($photoMode !== $lastPhotoMode) {
                $this->goProControlService->setPhotoMode($photoMode);
                $lastPhotoMode = $photoMode;
            }
            echo date("Y-m-d H:i:s") . " Taking photo\n";
            $this->goProControlService->triggerPhoto();
            echo date("Y-m-d H:i:s") . " Downloading photos\n";
            $this->downloadAllImages($folder);
            $this->checkBatteryState();
            $this->waitUntilNextImageCapture($interval);
        }
    }

    private function determineMode(): int
    {
        date_default_timezone_set('Europe/Madrid'); // Set time zone for Madrid

        // Latitude and longitude for Madrid, Spain
        $latitude = 40.4168;
        $longitude = -3.7038;

        // Get sunrise and sunset times in Unix timestamp format
        $sun_info = date_sun_info(time(), $latitude, $longitude);
        $sunrise = $sun_info['sunrise'];
        $sunset = $sun_info['sunset'];

        // Calculate 30 minutes after sunrise and 1 hour before sunset
        $start = $sunrise + 30 * 60; // 30 minutes in seconds
        $end = $sunset - 60 * 60; // 1 hour in seconds

        $currentTime = time(); // Current time in seconds since Unix epoch

        if ($currentTime >= $start && $currentTime <= $end) {
            return self::NORMAL_PHOTO_MODE;
        }

        return self::NIGHT_PHOTO_MODE;
    }

    private function downloadAllImages(string $folder): void
    {
        try {
            $imageUrls = $this->goProMediaService->getImageUrls();

            if (empty($imageUrls)) {
                return;
            }

            if (count($imageUrls) > 1) {
                echo date("Y-m-d H:i:s") . " Found " . count($imageUrls) . " photos\n";
            }
            $i = 0;
            foreach ($imageUrls as $url) {
                if (count($imageUrls) > 1) {
                    echo $i++ . "/" . count($imageUrls) . "\n";
                }
                $filename = basename($url);
                $filepath = $folder . '/' . $filename;

                $this->goProMediaService->downloadImage($url, $filepath);
            }

            $this->goProMediaService->deleteAllImages();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }

    }

    protected function checkBatteryState(): void
    {
        $batteryState = $this->goProControlService->getBatteryState();
        switch ($batteryState) {
            case BatteryStateEnum::CHARGING:
                echo "Battery is charging" . PHP_EOL;
                break;
            case BatteryStateEnum::FULL:
                echo "Battery is full" . PHP_EOL;
                break;
            case BatteryStateEnum::HALF_FULL:
                echo "Powerbank is empty - Please replace!" . PHP_EOL;
                break;
            case BatteryStateEnum::LOW:
                echo "Powerbank is empty - Please replace, Battery already low!" . PHP_EOL;
                break;
            case BatteryStateEnum::EMPTY:
                echo "Powerbank is empty - Please replace immediately, Battery as good as dead!" . PHP_EOL;
                break;
            case BatteryStateEnum::UNKNOWN:
                echo "Error getting battery state!" . PHP_EOL;
                break;
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
