<?php
declare(strict_types=1);

namespace Reifinger\Business\ImageStreamTimelapse\Event;

interface EventInterface
{

    public function reportMeta(int $totalSeconds, int $totalFrames, int $targetSeconds): void;

    public function reportProgress(): void;
}
