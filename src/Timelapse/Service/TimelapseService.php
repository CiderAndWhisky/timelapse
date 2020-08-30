<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Reifinger\Timelapse\Model\Config;

class TimelapseService
{
    public bool $force = false;
    public bool $keepTempImages = true;
    private ImageWriterService $imageWriterService;
    private RendererService $rendererService;
    private VideoMakerService $videoMakerService;
    private FileService $fileService;
    private PathService $imagePathService;
    private Config $config;

    public function __construct(
            Config $config,
            ImageWriterService $imageWriterService,
            RendererService $rendererService,
            VideoMakerService $videoMakerService,
            FileService $fileService
    ) {
        $this->imageWriterService = $imageWriterService;
        $this->rendererService = $rendererService;
        $this->videoMakerService = $videoMakerService;
        $this->fileService = $fileService;
        $this->config = $config;
    }

    public function createTimelapse(
            OutputServiceInterface $outputService
    ): string {
        $this->imageWriterService->initializeOutputDirectory($this->force);

        foreach ($this->config->scenes as $scene) {
            $frameCount = $this->rendererService->getFramesInScene($scene);
            $outputService->startProgress($scene->name, $frameCount);
            for ($imageNr = 0; $imageNr < $frameCount; $imageNr++) {
                $this->rendererService->renderImage($scene, $imageNr, $this->imageWriterService);
                $outputService->onImageRendered();
            }
            $outputService->stopProgress();
        }

        $videoPath = $this->videoMakerService->mergeImages();
        $outputService->success($videoPath);

        if (!$this->keepTempImages) {
            $this->fileService->removeDirectoryRecursive($this->imagePathService->getTmpPath());
        }

        return $videoPath;
    }
}
