<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

interface OutputServiceInterface
{
    public function startProgress(string $name, int $frameCount): void;

    public function onImageRendered(): void;

    public function write(string $message): void;

    public function success(string $videoPath): void;

    public function error(string $message): void;

    public function stopProgress(): void;
}
