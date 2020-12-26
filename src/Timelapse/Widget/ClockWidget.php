<?php
declare(strict_types=1);

namespace Reifinger\Timelapse\Widget;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use Reifinger\Timelapse\Model\RenderImageInformation;

class ClockWidget implements WidgetInterface
{
    private const STROKE_WIDTH = 10;
    private float $top;
    private float $left;
    private float $height;

    public function __construct(float $top, float $left, float $height)
    {
        $this->top = $top;
        $this->left = $left;
        $this->height = $height;
    }

    public function render(RenderImageInformation $renderImageInformation, Imagick $frame): void
    {
        if (!$renderImageInformation->timestamp) {
            return;
        }
        $radius = $frame->getImageWidth() * $this->height / 200;
        $centerX = $frame->getImageWidth() * ($this->left + $this->height / 2) / 100;
        $centerY = $frame->getImageHeight() * ($this->top + $this->height / 2) / 100;
        $hour = date('h', $renderImageInformation->timestamp);
        $min = date('i', $renderImageInformation->timestamp);
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel('none'));
        $draw->setStrokeColor(new ImagickPixel('#fff'));
        $draw->setStrokeWidth(self::STROKE_WIDTH);
        $draw->circle($centerX, $centerY, $centerX + $radius, $centerY);
        $radians = 2 * M_PI * $hour / 12;
        $draw->polyline([['x' => $centerX, 'y' => $centerY], ['x' => $centerX + sin($radians) * $radius / 2, 'y' => $centerY - cos($radians) * $radius / 2]]);
        $draw->setStrokeWidth(self::STROKE_WIDTH / 2.0);
        $radians = 2 * M_PI * $min / 60;
        $draw->polyline([['x' => $centerX, 'y' => $centerY], ['x' => $centerX + sin($radians) * ($radius - 3 * self::STROKE_WIDTH), 'y' => $centerY - cos($radians) * ($radius - 3 * self::STROKE_WIDTH)]]);
        $draw->setFillColor(new ImagickPixel('#fff'));
        $draw->setStrokeWidth(0);
        $draw->circle($centerX, $centerY, $centerX + self::STROKE_WIDTH, $centerY);

        $frame->drawImage($draw);
    }
}
