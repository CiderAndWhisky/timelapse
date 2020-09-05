<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Reifinger\Timelapse\Model\RenderImageInformation;

class RenderQueueService
{
    /** @var RenderImageInformation[] */
    private array $queue = [];

    public function addToQueue(RenderImageInformation $renderImageInformation): void
    {
        $this->queue[] = $renderImageInformation;
    }

    public function getNext(): ?RenderImageInformation
    {
        return array_shift($this->queue);
    }

    public function hasImagesLeft(): bool
    {
        return $this->size() > 0;
    }

    public function size(): int
    {
        return count($this->queue);
    }
}
