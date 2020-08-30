<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Reifinger\Timelapse\Model\Config;

class VideoMakerService
{
    private PathService $pathService;
    private Config $config;

    public function __construct(Config $config, PathService $pathService)
    {
        $this->pathService = $pathService;
        $this->config = $config;
    }

    public function mergeImages(): string
    {
        $options = [
                'pattern_type' => 'glob',
                'framerate' => $this->config->output->fps,
                'i' => '\''.$this->pathService->getTmpPath().'/*.png\'',
                'c:v' => 'libx264',
                'profile:v' => 'high',
                'crf' => '20',
                'pix_fmt' => 'yuv420p',
        ];
        $optionsString = '';
        array_walk(
                $options,
                static function ($value, $key) use (&$optionsString) {
                    $optionsString .= ' -'.$key.' '.$value;
                }
        );
        $targetFile = $this->pathService->getOutputPath().'/output.mp4';

        exec('ffmpeg '.$optionsString.' '.$targetFile);

        return $targetFile;
    }
}
