<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Reader;

use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Model\Scene;
use Reifinger\Timelapse\Model\Vector2D;
use Reifinger\Timelapse\Model\VideoOutput;
use Reifinger\Timelapse\Model\Zoom;
use Reifinger\Timelapse\Widget\WidgetInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigReader
{
    public function applyTo(Config $config, string $configFile): void
    {
        $rootDir = dirname($configFile);
        $yaml = Yaml::parseFile($configFile);
        $outputValues = $yaml['output'];

        $config->srcRootPath = $yaml['srcRootPath'] ?? $rootDir;

        $config->output = new VideoOutput();
        $config->output->path = $outputValues['path'] ?? $rootDir . '/output';
        $config->output->fps = $outputValues['fps'];
        $config->output->resolution = new Vector2D(
                $outputValues['resolution']['width'],
                $outputValues['resolution']['height']
        );

        $config->widgets = $this->parseWidgetInfo($yaml);

        $config->scenes = $this->parseScenesInfo($yaml['scenes']);
    }

    /**
     * @param array $yaml
     * @return WidgetInterface[]
     */
    private function parseWidgetInfo(array $yaml): array
    {
        $widgets = [];
        if (array_key_exists('widgets', $yaml)) {
            foreach ($yaml['widgets'] as $widgetName => $widgetInfo) {
                $className = '\Reifinger\Timelapse\Widget\\' . ucfirst($widgetName) . 'Widget';
                $widgets[] = new $className((float)$widgetInfo['top'], (float)$widgetInfo['left'], (float)$widgetInfo['height']);
            }
        }
        return $widgets;
    }

    protected function parseScenesInfo(array $yaml): array
    {
        $scenes = [];
        foreach ($yaml as $sceneNode) {
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
            $scenes[] = $scene;
        }
        return $scenes;
    }
}
