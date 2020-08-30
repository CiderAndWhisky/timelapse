<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Model;

class Config
{
    /** @var Scene[] */
    public array $scenes;
    public VideoOutput $output;
    public string $srcRootPath;
}
