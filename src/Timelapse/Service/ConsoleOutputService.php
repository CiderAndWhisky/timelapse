<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleOutputService implements OutputServiceInterface
{
    private SymfonyStyle $io;
    private ProgressBar $currentProgress;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function startProgress(string $name, int $frameCount): void
    {
        $this->io->section('Calculating '.$name);
        $this->currentProgress = $this->io->createProgressBar($frameCount);
        $this->currentProgress->setFormat(
                ' %current%/%max% [%bar%] %percent:3s%% %remaining:6s%/%estimated:-6s% remaining'
        );
    }

    public function onImageRendered(): void
    {
        $this->currentProgress->advance();
    }

    public function write(string $message): void
    {
        $this->io->text($message);
    }

    public function success(string $videoPath): void
    {
        $this->io->success('Created timelapse video: '.$videoPath);
    }

    public function error(string $message): void
    {
        $this->io->error($message);
    }

    public function stopProgress(): void
    {
        $this->currentProgress->finish();
        $this->io->writeln('');
        $this->io->writeln('');
    }
}
