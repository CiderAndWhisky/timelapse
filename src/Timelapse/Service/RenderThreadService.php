<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Reifinger\Timelapse\Model\RenderImageInformation;

class RenderThreadService
{
    private RenderImageInformation $renderImageInformation;
    /** @var resource */
    private $thread;

    public function __construct(
            RenderImageInformation $renderImageInformation
    ) {
        $this->renderImageInformation = $renderImageInformation;
    }

    public function start(): void
    {
        $command = [
                dirname(__DIR__, 3).'/bin/timelapse',
                'frame:render',
                serialize($this->renderImageInformation),
        ];
        $descriptorspec = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                12 => array("pipe", "w"),
        );
        $this->thread = proc_open($command, $descriptorspec, $pipes);
    }

    public function isRunning(): bool
    {
        return proc_get_status($this->thread)['running'] ?? false;
    }
}
