<?php
declare(strict_types=1);

namespace Reifinger\Business\ProcessImageStream\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Imagick;

readonly class UploaderService
{
    private Client $client;

    public function __construct(
            private string $uploadUri,
            private string $username,
            private string $password,
    )
    {
        $this->client = new Client([
                'base_uri' => $this->uploadUri,
                'timeout' => 10,
        ]);
    }


    public function upload(string $imagePath): void
    {
        $resizedImagePath = $this->resizeImage($imagePath, 800);

        try {
            $response = $this->client->post($this->uploadUri,
                    [
                            'auth' => [$this->username, $this->password],
                            'multipart' => [
                                    [
                                            'name' => 'image',
                                            'contents' => fopen($resizedImagePath, 'rb'),
                                            'filename' => basename($imagePath)
                                    ],
                            ],
                    ]);
            echo $response->getBody()->getContents() . PHP_EOL;
        } catch (GuzzleException $e) {
            echo "Could not upload image:\n" . $e->getMessage() . PHP_EOL . $e->getResponse()->getBody()->getContents() . PHP_EOL;
        }
    }


    // Resize the image to a given width while maintaining aspect ratio
    private function resizeImage(string $originalPath, int $newWidth): string
    {
        $image = new Imagick($originalPath);
        $image->resizeImage($newWidth, 0, Imagick::FILTER_LANCZOS, 1);
        $resizedPath = '/tmp/upload.jpg';
        $image->writeImage($resizedPath);
        return $resizedPath;
    }
}
