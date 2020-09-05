<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Model;

class Scene
{
    public string $name;
    public float $duration;
    public int $startNr;
    public int $endNr;
    public string $imageNameTemplate;
    public Zoom $zoomTo;
    public Zoom $zoomFrom;
}
