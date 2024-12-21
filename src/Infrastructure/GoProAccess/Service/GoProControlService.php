<?php
declare(strict_types=1);

namespace Reifinger\Infrastructure\GoProAccess\Service;

use GuzzleHttp\Client;
use JsonException;
use Reifinger\Infrastructure\GoProAccess\Enum\BatteryStateEnum;
use Throwable;

class GoProControlService
{

    private Client $apiClient;

    public function __construct(
            private readonly string $goproIp,
    )
    {
        $this->apiClient = new Client([
                'base_uri' => "http://{$this->goproIp}/",
                'timeout' => 2.0,
        ]);
    }

    public function setupGoPro(int $photoMode): void
    {
        $this->setPhotoMode($photoMode);
        $this->setProTuneOn();
        $this->setResolution12MPLinear();
        $this->setISO200();
    }

    public function setPhotoMode(int $photoMode): void
    {
        $this->call('command/sub_mode?mode=1&sub_mode=' . $photoMode);
    }

    private function call(string $endpoint): string
    {
        return $this->apiClient->get('gp/gpControl/' . $endpoint)->getBody()->getContents();
    }

    private function setProTuneOn(): void
    {
        $this->call('setting/21/1');
    }

    private function setResolution12MPLinear(): void
    {
        $this->call('setting/17/10');
    }

    private function setISO100(): void
    {
        $this->call('setting/24/3');
    }

    public function triggerPhoto(): void
    {
        $this->call("command/shutter?p=1");
        $occupied = true;
        while ($occupied) {
            try {
                $json = $this->call("status");
            } catch (Throwable) {
                continue;
            }
            try {
                $status = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
                if ($status->status->{8} !== 1) {
                    $occupied = false;
                }
            } catch (JsonException) {
            }
        }
    }

    public function getBatteryState(): BatteryStateEnum
    {
        try {
            $json = $this->call("status");
            $status = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
            return BatteryStateEnum::from($status->status->{2});
        } catch (\Exception) {
            return BatteryStateEnum::UNKNOWN;
        }
    }

    private function setResolution12MPNarrow(): void
    {
        $this->call('setting/17/9');
    }

    private function setResolution12MPWide(): void
    {
        $this->call('setting/17/0');
    }

    private function setISO800()
    {
        $this->call('setting/24/0');
    }

    private function setISO200()
    {
        $this->call('setting/24/2');
    }
}
