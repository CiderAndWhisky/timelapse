<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Reifinger\Timelapse\Model\Config;

class PathService
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getTmpPath(): string
    {
        return $this->getOutputPath().'/tmp';
    }

    public function getOutputPath(): string
    {
        return $this->config->output->path;
    }
}
