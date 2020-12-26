<?php
declare(strict_types=1);

namespace Reifinger\Timelapse\Service;


use Imagick;
use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Model\RenderImageInformation;

class WidgetPainterService
{
    /** @var Config */
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function addWidgets(RenderImageInformation $renderInfo, Imagick $frame): void
    {
        foreach ($this->config->widgets as $widget) {
            $widget->render($renderInfo, $frame);
        }
    }
}
