<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Model;

class RenderImageInformation
{
    public int $targetWidth;
    public int $targetHeight;
    public string $imageNameTemplate;
    public int $baseImageNumber;
    public Zoom $zoom;
    public float $overlayOpacity;
    public bool $isLastImageInScene;
    public int $frameNumber;
    public string $srcRootPath;
    public string $targetPath;

    public function __construct(
            int $targetWidth,
            int $targetHeight,
            string $imageNameTemplate,
            int $baseImageNumber,
            Zoom $zoom,
            $overlayOpacity,
            bool $isLastImageInScene,
            int $frameNumber,
            string $srcRootPath,
            string $targetPath
    ) {
        $this->targetWidth = $targetWidth;
        $this->targetHeight = $targetHeight;
        $this->imageNameTemplate = $imageNameTemplate;
        $this->baseImageNumber = $baseImageNumber;
        $this->zoom = $zoom;
        $this->overlayOpacity = $overlayOpacity;
        $this->isLastImageInScene = $isLastImageInScene;
        $this->frameNumber = $frameNumber;
        $this->srcRootPath = $srcRootPath;
        $this->targetPath = $targetPath;
    }
}
