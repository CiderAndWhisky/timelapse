<?php
declare(strict_types=1);

namespace Reifinger\Api\ImageStreamTimelapse\Formatter;

use Reifinger\Business\ImageStreamTimelapse\Event\EventInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EventFormatter implements EventInterface
{
    private float $startTime;
    private \Symfony\Component\Console\Helper\ProgressBar $progressBar;

    public function __construct(private readonly SymfonyStyle $io)
    {
    }

    public function reportMeta(int $totalSeconds, int $totalFrames, int $targetSeconds): void
    {
        $this->io->writeln(sprintf('Source length: %s', $this->formatSeconds($totalSeconds)));
        $this->io->writeln(sprintf('Target length: %s', $this->formatSeconds($targetSeconds)));
        $this->io->writeln(sprintf('Frames to calculate: %d', $totalFrames));
        $this->startTime = microtime(true);
        $this->progressBar = $this->io->createProgressBar($totalFrames);
        // Custom format definition
        $format = '%current%/%max% [%bar%] %percent:3s%% %message%';
        $this->progressBar->setFormat($format);
    }

    protected function formatSeconds(int $totalSeconds): string
    {
        $hours = intdiv($totalSeconds, 3600);
        $remainingSeconds = $totalSeconds % 3600;

        $minutes = intdiv($remainingSeconds, 60);
        $seconds = $remainingSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }


    public function reportProgress(): void
    {
        // Calculate elapsed and remaining time
        $elapsedTime = microtime(true) - $this->startTime;
        $frame = $this->progressBar->getProgress() + 1;
        $totalFrames = $this->progressBar->getMaxSteps();
        $remainingTime = ($frame < $totalFrames) ? ($elapsedTime / $frame) * ($totalFrames - $frame) : 0;

        // Show elapsed and remaining time
        $this->progressBar->setMessage(sprintf('Elapsed: %d secs | Remaining: %d secs', $elapsedTime, $remainingTime));
        $this->progressBar->advance();
    }
}
