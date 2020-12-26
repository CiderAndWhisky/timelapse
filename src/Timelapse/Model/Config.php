<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Model;

use Reifinger\Timelapse\Widget\WidgetInterface;

class Config
{
    /** @var Scene[] */
    public array $scenes;
    public VideoOutput $output;
    public string $srcRootPath;
    /** @var WidgetInterface[] */
    public array $widgets;
}
