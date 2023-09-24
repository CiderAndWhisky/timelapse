<?php
declare(strict_types=1);

namespace Reifinger\Infrastructure\GoProAccess\Service;

use GuzzleHttp\Client;

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

    public function setupGoPro(): void
    {
        $this->setPhotoMode();
        $this->setResolution12MPNarrow();
        $this->setISO100();
    }

    protected function setPhotoMode(): void
    {
        $this->call('command/sub_mode?mode=1&sub_mode=1');
    }

    private function call(string $endpoint): string
    {
        return $this->apiClient->get('gp/gpControl/' . $endpoint)->getBody()->getContents();
    }

    private function setResolution12MPNarrow(): void
    {
        $this->call('setting/17/9');
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
            } catch (\Throwable $e) {
                continue;
            }
            try {
                $status = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
                if ($status->status->{8} !== 1) {
                    $occupied = false;
                }
            } catch (\JsonException $e) {
            }
        }
    }

    private function setResolution12MPWide(): void
    {
        $this->call('setting/17/0');
    }
}
