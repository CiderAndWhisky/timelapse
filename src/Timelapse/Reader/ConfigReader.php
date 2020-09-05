<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Reader;

use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Model\Scene;
use Reifinger\Timelapse\Model\Vector2D;
use Reifinger\Timelapse\Model\VideoOutput;
use Reifinger\Timelapse\Model\Zoom;
use Symfony\Component\Yaml\Yaml;

class ConfigReader
{
    public function applyTo(Config $config, string $configFile): void
    {
        $yaml = Yaml::parseFile($configFile);
        $outputValues = $yaml['output'];

        $config->srcRootPath = $yaml['srcRootPath'];

        $config->output = new VideoOutput();
        $config->output->path = $outputValues['path'];
        $config->output->fps = $outputValues['fps'];
        $config->output->resolution = new Vector2D(
            $outputValues['resolution']['width'],
            $outputValues['resolution']['height']
        );

        $config->scenes = [];
        foreach ($yaml["scenes"] as $sceneNode) {
            $scene = new Scene();
            $scene->name = $sceneNode['name'];
            $scene->duration = (float)$sceneNode['duration'];
            $scene->startNr = (int)$sceneNode['start'];
            $scene->endNr = (int)$sceneNode['end'];
            $scene->imageNameTemplate = (string)$sceneNode['imageName'];
            $scene->zoomFrom = Zoom::empty();
            if (array_key_exists('zoomFrom', $sceneNode)) {
                $scene->zoomFrom->topLeft = new Vector2D($sceneNode['zoomFrom']['left'], $sceneNode['zoomFrom']['top']);
                $scene->zoomFrom->sizeInPercentage = $sceneNode['zoomFrom']['size'];
            }
            $scene->zoomTo = Zoom::empty();
            if (array_key_exists('zoomTo', $sceneNode)) {
                $scene->zoomTo->topLeft = new Vector2D($sceneNode['zoomTo']['left'], $sceneNode['zoomTo']['top']);
                $scene->zoomTo->sizeInPercentage = $sceneNode['zoomTo']['size'];
            }
            $config->scenes[] = $scene;
        }
    }
}
