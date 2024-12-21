<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Model\Scene;
use Reifinger\Timelapse\Model\Zoom;

class TimelapseService
{
    public bool $force = false;
    public bool $keepTempImages = true;
    public int $useCPUCores;
    private ImageWriterService $imageWriterService;
    private RendererService $rendererService;
    private VideoMakerService $videoMakerService;
    private FileService $fileService;
    private PathService $imagePathService;
    private Config $config;
    private RenderQueueService $renderQueueService;
    public string $configFile;

    public function __construct(
            Config $config,
            ImageWriterService $imageWriterService,
            RendererService $rendererService,
            VideoMakerService $videoMakerService,
            FileService $fileService,
            PathService $imagePathService,
            RenderQueueService $renderQueueService
    ) {
        $this->imageWriterService = $imageWriterService;
        $this->rendererService = $rendererService;
        $this->videoMakerService = $videoMakerService;
        $this->fileService = $fileService;
        $this->config = $config;
        $this->imagePathService = $imagePathService;
        $this->renderQueueService = $renderQueueService;
    }

    public function createTimelapse(
            OutputServiceInterface $outputService
    ): string {
        $this->imageWriterService->initializeOutputDirectory(
                $this->imagePathService->getOutputPath(),
                $this->imagePathService->getTmpPath(),
                $this->force
        );

        if ($this->useCPUCores > 1) {
            $this->renderParallel($outputService);
        } else {
            $this->renderSerial($outputService);
        }

        $videoPath = $this->videoMakerService->mergeImages();
        $outputService->success($videoPath);

        if (!$this->keepTempImages) {
            $this->fileService->removeDirectoryRecursive($this->imagePathService->getTmpPath());
        }

        return $videoPath;
    }

    private function renderParallel(OutputServiceInterface $outputService): void
    {
        $zoom = Zoom::default();
        $frameNumber = 1;
        foreach ($this->config->scenes as $scene) {
            $this->useZoomSettingFromLastSceneIfUnsetInScene($scene, $zoom);

            $frameCount = $this->rendererService->getFramesInScene($scene);

            for ($imageNr = 0; $imageNr < $frameCount; $imageNr++) {
                $this->renderQueueService->addToQueue(
                        $this->rendererService->calculateRenderInformation(
                                $scene,
                                $imageNr,
                                $frameNumber++,
                                $this->imagePathService->getTmpPath()
                        )
                );
            }

            $zoom = $scene->zoomTo->isEmpty() ? $scene->zoomFrom : $scene->zoomTo;
        }

        $imageCount = $this->renderQueueService->size();
        $outputService->startProgress(
                'Rendering images on '.$this->useCPUCores.' cores',
                $imageCount
        );
        $threads = [];
        while ($this->renderQueueService->hasImagesLeft() || count($threads)) {
            /** @var RenderThreadService $thread */
            foreach ($threads as $threadId => $thread) {
                if (!$thread->isRunning()) {
                    unset($threads[$threadId]);
                    $outputService->onImageRendered();
                }
            }
            if (count($threads) < $this->useCPUCores) {
                $renderImageInformation = $this->renderQueueService->getNext();
                if ($renderImageInformation) {
                    $thread = new RenderThreadService($renderImageInformation, $this->configFile);
                    $thread->start();
                    $threads[] = $thread;
                }
            }
            usleep(100_000);
        }
        $outputService->stopProgress();
    }

    /**
     * @param OutputServiceInterface $outputService
     */
    protected function renderSerial(OutputServiceInterface $outputService): void
    {
        $zoom = Zoom::default();
        $frameNr = 1;
        foreach ($this->config->scenes as $scene) {
            $this->useZoomSettingFromLastSceneIfUnsetInScene($scene, $zoom);

            $frameCount = $this->rendererService->getFramesInScene($scene);

            $outputService->startProgress($scene->name, $frameCount);
            for ($imageNr = 0; $imageNr < $frameCount; $imageNr++) {
                $frame = $this->rendererService->calculateAndRenderImage(
                        $scene,
                        $imageNr,
                        $frameNr,
                        $this->imagePathService->getTmpPath()
                );
                $this->imageWriterService->writeImage($frame, $frameNr, $this->imagePathService->getTmpPath());
                $frameNr++;
                $outputService->onImageRendered();
            }
            $outputService->stopProgress();

            $zoom = $scene->zoomTo->isEmpty() ? $scene->zoomFrom : $scene->zoomTo;
        }
    }

    protected function useZoomSettingFromLastSceneIfUnsetInScene(Scene $scene, Zoom $zoom): void
    {
        if ($scene->zoomFrom->isEmpty()) {
            $scene->zoomFrom = $zoom;
        }
        if ($scene->zoomTo->isEmpty()) {
            $scene->zoomTo = $zoom;
        }
    }
}
