<?php
declare(strict_types=1);

namespace Reifinger\Timelapse\Widget;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use Reifinger\Timelapse\Model\RenderImageInformation;

class TimeWidget implements WidgetInterface
{
    private const POINTSIZE = 60;
    private const SHADOW_SIZE = 5;
    private float $top;
    private float $left;

    /** @noinspection PhpUnusedParameterInspection */
    public function __construct(float $top, float $left, float $unused)
    {
        $this->top = $top;
        $this->left = $left;
    }

    public function render(RenderImageInformation $renderImageInformation, Imagick $frame): void
    {
        if (!$renderImageInformation->timestamp) {
            return;
        }
        $date = date('H:i:s', $renderImageInformation->timestamp);
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel('#000'));
        $draw->setFontSize(self::POINTSIZE);
        $frame->annotateImage($draw, $frame->getImageWidth() * $this->left / 100, $frame->getImageHeight() * $this->top / 100, 0, $date);
        $draw->setFillColor(new ImagickPixel('#fff'));
        $frame->annotateImage($draw, $frame->getImageWidth() * $this->left / 100 - self::SHADOW_SIZE, $frame->getImageHeight() * $this->top / 100 - self::SHADOW_SIZE, 0, $date);
    }
}
