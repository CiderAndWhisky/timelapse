<?php

declare(strict_types=1);

namespace Reifinger\Api\ImageCapture\Command;

use Reifinger\Infrastructure\GoProAccess\Service\GoProMediaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
        name: 'timelapse:image:mass-download',
        description: 'Downloads all images from GoPro.'
)]
class ImageMassDownloadCommand extends Command
{
    public function __construct(private readonly GoProMediaService $goProMediaService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'The folder to download images to.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $folder = $input->getArgument('folder');

        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            $io->error('Could not create folder ' . $folder);
            return Command::FAILURE;
        }

        $io->text('Fetching list of images...');

        $imageUrls = $this->goProMediaService->getImageUrls();

        if (empty($imageUrls)) {
            $io->comment('No images found.');
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, count($imageUrls));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');

        foreach ($imageUrls as $url) {
            $filename = basename($url);
            $filepath = $folder . '/' . $filename;

            $this->goProMediaService->downloadImage($url, $filepath);

            /** @noinspection DisconnectedForeachInstructionInspection */
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);
        $io->success('Download complete.');

        return Command::SUCCESS;
    }
}
