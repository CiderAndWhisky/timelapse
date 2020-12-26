<?php
declare(strict_types=1);

namespace Reifinger\Timelapse\Widget;


use Imagick;
use Reifinger\Timelapse\Model\RenderImageInformation;

interface WidgetInterface
{

    public function render(RenderImageInformation $renderImageInformation, Imagick $frame): void;
}
