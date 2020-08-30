<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Model;

class Zoom
{
    public Vector2D $topLeft;
    public float $sizeInPercentage;

    public function __construct(float $top, float $left, float $width)
    {
        $this->topLeft = new Vector2D($left, $top);
        $this->sizeInPercentage = $width;
    }

    public static function default(): Zoom
    {
        return new Zoom(0, 0, 100);
    }
}
