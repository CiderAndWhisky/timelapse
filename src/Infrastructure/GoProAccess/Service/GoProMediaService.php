<?php
declare(strict_types=1);

namespace Reifinger\Infrastructure\GoProAccess\Service;

use GuzzleHttp\Client;

class GoProMediaService
{
    private Client $cherokeeClient;
    private Client $apiClient;

    public function __construct(
            private readonly string $goproIp,
            private readonly int    $goproCherokeePort
    )
    {
        $this->cherokeeClient = new Client([
                'base_uri' => "http://{$this->goproIp}:{$this->goproCherokeePort}/",
                'timeout' => 10.0,
        ]);
        $this->apiClient = new Client([
                'base_uri' => "http://{$this->goproIp}/",
                'timeout' => 10.0,
        ]);
    }

    public function getImageUrls(): array
    {
        // Fetch the directory listing content
        $response = $this->cherokeeClient->get('videos/DCIM/');
        $htmlContent = $response->getBody()->getContents();

        // Regex to match directory names
        preg_match_all('#<a href="/videos/DCIM/([^"]+/)">#', $htmlContent, $matches);

        $imageUrls = [];
        if (isset($matches[1])) {
            foreach ($matches[1] as $folder) {
                // Trim the trailing slash from the folder name
                $folder = rtrim($folder, '/');

                // Here you are assumed to make an HTTP request to get images in the directory
                $folderImages = $this->getImagesInFolder($folder);
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $imageUrls = array_merge($imageUrls, $folderImages);
            }
        }

        return $imageUrls;
    }

    private function getImagesInFolder(string $folder): array
    {
        $response = $this->cherokeeClient->get("videos/DCIM/{$folder}/");
        $htmlContent = $response->getBody()->getContents();

        // Regex to match image file names (assumes JPG files)
        preg_match_all('#<a href="/videos/DCIM/' . preg_quote($folder, '#') . '/([^"]+\.JPG)">#', $htmlContent, $matches);

        $imageUrls = [];
        if (isset($matches[1])) {
            foreach ($matches[1] as $filename) {
                $imageUrls[] = "http://{$this->goproIp}:{$this->goproCherokeePort}/videos/DCIM/{$folder}/{$filename}";
            }
        }

        return $imageUrls;
    }


    public function downloadImage(string $url, string $targetPath): void
    {
        if (file_exists($targetPath) && filesize($targetPath) > 0) {
            return;
        }
        $response = $this->cherokeeClient->get($url);
        file_put_contents($targetPath, $response->getBody()->getContents());
    }

    public function downloadLastImage(string $targetPath): void
    {
        // Download the last image from GoPro to $targetPath
        // Note: Implement this operation using GoPro's SDK or API
    }

    public function deleteLastImage(): void
    {
        // Delete the last image from GoPro
        // Note: Implement this operation using GoPro's SDK or API
    }
}
