<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Imagick;
use ImagickPixel;
use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Model\RenderImageInformation;
use Reifinger\Timelapse\Model\Scene;
use Reifinger\Timelapse\Model\Zoom;

class RendererService
{
    private Zoom $zoomFrom;
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function renderImage(Scene $scene, int $sceneFrameNumber, int $frameNumber, string $targetPath): Imagick
    {
        $renderImageInformation = $this->calculateRenderInformation(
                $scene,
                $sceneFrameNumber,
                $frameNumber,
                $targetPath
        );

        return $this->generateImage($renderImageInformation);
    }

    public function calculateRenderInformation(
            Scene $scene,
            int $sceneFrameNumber,
            int $frameNumber,
            string $targetPath
    ): RenderImageInformation {
        $sceneImageCount = $scene->endNr - $scene->startNr + 1;
        $frameCount = $this->getFramesInScene($scene);

        $progress = $sceneFrameNumber / $frameCount;

        $imageNumberInScene = $progress * $sceneImageCount;
        $overlayOpacity = $imageNumberInScene - (int)$imageNumberInScene;

        $baseImageNumber = $scene->startNr + (int)$imageNumberInScene;
        $zoom = $this->calculateZoom(
                $scene->zoomFrom,
                $scene->zoomTo,
                $progress
        );

        $targetWidth = (int)$this->config->output->resolution->x;
        $targetHeight = (int)$this->config->output->resolution->y;

        return new RenderImageInformation(
                $targetWidth,
                $targetHeight,
                $scene->imageNameTemplate,
                $baseImageNumber,
                $zoom,
                $overlayOpacity,
                $baseImageNumber === $scene->endNr,
                $frameNumber,
                $this->config->srcRootPath,
                $targetPath
        );
    }

    public function generateImage(RenderImageInformation $renderInfo): Imagick
    {
        $frame = $this->generateFrameCanvas($renderInfo->targetWidth, $renderInfo->targetHeight);

        $frame->compositeImage(
                $this->getSourceImage(
                        $renderInfo->imageNameTemplate,
                        $renderInfo->baseImageNumber,
                        $renderInfo->zoom,
                        $renderInfo->srcRootPath,
                        $renderInfo->targetWidth,
                        $renderInfo->targetHeight
                ),
                Imagick::COMPOSITE_OVER,
                0,
                0
        );

        if ($renderInfo->overlayOpacity > .01 && !$renderInfo->isLastImageInScene) {
            $overlayImage = $this->getSourceImage(
                    $renderInfo->imageNameTemplate,
                    $renderInfo->baseImageNumber + 1,
                    $renderInfo->zoom,
                    $renderInfo->srcRootPath,
                    $renderInfo->targetWidth,
                    $renderInfo->targetHeight
            );
            $overlayImage->setImageOpacity($renderInfo->overlayOpacity);
            $frame->compositeImage(
                    $overlayImage,
                    Imagick::COMPOSITE_OVER,
                    0,
                    0
            );
        }

        return $frame;
    }

    public function getFramesInScene(Scene $scene): int
    {
        return (int)ceil($scene->duration * $this->config->output->fps);
    }

    private function calculateZoom(Zoom $zoomFrom, Zoom $zoomTo, float $factor): Zoom
    {
        return new Zoom(
                $this->calculateBlend($zoomFrom->topLeft->y, $zoomTo->topLeft->y, $factor),
                $this->calculateBlend($zoomFrom->topLeft->x, $zoomTo->topLeft->x, $factor),
                $this->calculateBlend($zoomFrom->sizeInPercentage, $zoomTo->sizeInPercentage, $factor)
        );
    }

    /**
     * @param int $targetWidth
     * @param int $targetHeight
     * @return Imagick
     */
    protected function generateFrameCanvas(int $targetWidth, int $targetHeight): Imagick
    {
        $frame = new Imagick();
        $frame->newImage($targetWidth, $targetHeight, new ImagickPixel("black"));
        $frame->setImageFormat("png");

        return $frame;
    }

    private function getSourceImage(
            string $imageNameTemplate,
            int $imageNr,
            Zoom $zoom,
            string $srcRootPath,
            int $targetWidth,
            int $targetHeight
    ): Imagick {
        $imagePath = $srcRootPath.'/'.$this->formatFilename($imageNameTemplate, $imageNr);

        $image = new Imagick($imagePath);
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $image->cropImage(
                (int)($width * $zoom->sizeInPercentage / 100.0),
                (int)($height * $zoom->sizeInPercentage / 100.0),
                (int)($width * $zoom->topLeft->x / 100.0),
                (int)($height * $zoom->topLeft->y / 100.0)
        );
        $image->resizeImage(
                $targetWidth,
                $targetHeight,
                Imagick::FILTER_LANCZOS,
                1
        );

        return $image;
    }

    private function calculateBlend(float $from, float $to, float $factor): float
    {
        return $from + $factor * ($to - $from);
    }

    private function formatFilename(string $imageNameTemplate, int $imageNr): string
    {
        preg_match('/{(\d)}/', $imageNameTemplate, $matches);
        $length = (int)$matches[1];
        $formattedImageNr = str_pad(''.$imageNr, $length, '0', STR_PAD_LEFT);

        return str_replace('{'.$length.'}', $formattedImageNr, $imageNameTemplate);
    }
}
