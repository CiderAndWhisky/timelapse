<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Model;

class VideoOutput
{
    public string $path;
    public int $fps;
    public Vector2D $resolution;
}
