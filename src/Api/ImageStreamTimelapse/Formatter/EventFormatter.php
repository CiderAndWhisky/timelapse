<?php
declare(strict_types=1);

namespace Reifinger\Api\ImageStreamTimelapse\Formatter;

use Reifinger\Business\ImageStreamTimelapse\Event\EventInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EventFormatter implements EventInterface
{
    public function __construct(private readonly SymfonyStyle $io)
    {
    }

    public function reportMeta(int $totalSeconds, int $totalFrames, int $targetSeconds): void
    {
        $this->io->writeln(sprintf('Source length: %s', $this->formatSeconds($totalSeconds)));
        $this->io->writeln(sprintf('Target length: %s', $this->formatSeconds($targetSeconds)));
        $this->io->writeln(sprintf('Frames to calculate: %d', $totalFrames));
        $this->io->progressStart($totalFrames);
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
        $this->io->progressAdvance();
    }
}
