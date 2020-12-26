<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use DateTimeImmutable;
use Imagick;
use ImagickPixel;
use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Model\RenderImageInformation;
use Reifinger\Timelapse\Model\Scene;
use Reifinger\Timelapse\Model\Zoom;
use Throwable;

class RendererService
{
    private Zoom $zoomFrom;
    private Config $config;
    private WidgetPainterService $widgetPainterService;

    public function __construct(Config $config, WidgetPainterService $widgetPainterService)
    {
        $this->config = $config;
        $this->widgetPainterService = $widgetPainterService;
    }

    public function calculateAndRenderImage(Scene $scene, int $sceneFrameNumber, int $frameNumber, string $targetPath): Imagick
    {
        $renderImageInformation = $this->calculateRenderInformation(
                $scene,
                $sceneFrameNumber,
                $frameNumber,
                $targetPath
        );

        return $this->renderImage($renderImageInformation);
    }

    public function calculateRenderInformation(
            Scene $scene,
            int $sceneFrameNumber,
            int $frameNumber,
            string $targetPath
    ): RenderImageInformation
    {
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

    private function calculateBlend(float $from, float $to, float $factor): float
    {
        return $from + $factor * ($to - $from);
    }

    public function renderImage(RenderImageInformation $renderInfo): Imagick
    {
        $frame = $this->generateFrameCanvas($renderInfo->targetWidth, $renderInfo->targetHeight);

        $firstImage = $this->getSourceImage(
                $renderInfo->imageNameTemplate,
                $renderInfo->baseImageNumber,
                $renderInfo->zoom,
                $renderInfo->srcRootPath,
                $renderInfo->targetWidth,
                $renderInfo->targetHeight
        );
        $firstDate = $this->getImageDate($firstImage);
        $secondDate = null;
        $frame->compositeImage(
                $firstImage,
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
            $secondDate = $this->getImageDate($overlayImage);
            $overlayImage->setImageOpacity($renderInfo->overlayOpacity);
            $frame->compositeImage(
                    $overlayImage,
                    Imagick::COMPOSITE_OVER,
                    0,
                    0
            );
        }
        $renderInfo->timestamp = $this->calculateTimestamp($firstDate, $secondDate, $renderInfo->overlayOpacity);

        $this->widgetPainterService->addWidgets($renderInfo, $frame);

        return $frame;
    }

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
    ): Imagick
    {
        $imagePath = $srcRootPath . '/' . $this->formatFilename($imageNameTemplate, $imageNr);

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

    private function formatFilename(string $imageNameTemplate, int $imageNr): string
    {
        preg_match('/{(\d)}/', $imageNameTemplate, $matches);
        $length = (int)$matches[1];
        $formattedImageNr = str_pad('' . $imageNr, $length, '0', STR_PAD_LEFT);

        return str_replace('{' . $length . '}', $formattedImageNr, $imageNameTemplate);
    }

    private function getImageDate(Imagick $image): ?DateTimeImmutable
    {
        try {
            $imageDateProperties = $image->getImageProperties('exif:DateTime');
            if (count($imageDateProperties) === 1) {
                return new DateTimeImmutable($imageDateProperties['exif:DateTime']);
            }

        } catch (Throwable $t) {
            echo sprintf('Error in Creation Date in %s', $image->getFilename());
        }
        return null;
    }

    private function calculateTimestamp(?DateTimeImmutable $firstDate, ?DateTimeImmutable $secondDate, float $overlayOpacity): ?int
    {
        if ($firstDate === null) {
            return null;
        }
        if ($secondDate === null) {
            return $firstDate->getTimestamp();
        }
        $diff = $secondDate->getTimestamp() - $firstDate->getTimestamp();
        return (int)($firstDate->getTimestamp() + $diff * $overlayOpacity);
    }
}
