<?php
declare(strict_types=1);

namespace Reifinger\Api\CaptureImages\Command;

use Reifinger\Business\CaptureImages\Service\ImageCaptureService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'images:capture', description: 'Capture and download images every x seconds')]
class ImageCaptureCommand extends Command
{
    public function __construct(private readonly ImageCaptureService $captureImagesService, private readonly SymfonyStyle $io)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'The folder to download images to. The current date will be appended automatically');
        $this->addOption('interval', 'i', InputArgument::OPTIONAL, 'The interval in seconds between image captures', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $folder = $input->getArgument('folder');
        $isoDate = date('Y-m-d');
        $folder .= '/' . $isoDate;

        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            $this->io->error('Could not create folder ' . $folder);
            return Command::FAILURE;
        }

        $interval = (int)$input->getOption('interval');
        if ($interval < 5) {
            $this->io->error('Interval must be at least 5 seconds');
            return Command::FAILURE;
        }
        if ($interval > 30) {
            $this->io->error('Interval must be at most 30 seconds');
            return Command::FAILURE;
        }
        $this->captureImagesService->captureImages($folder, $interval);

        return Command::SUCCESS;
    }
}

