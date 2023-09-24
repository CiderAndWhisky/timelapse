<?php
declare(strict_types=1);

namespace Reifinger\Api\ImageStreamTimelapse\Command;

use Reifinger\Business\ImageStreamTimelapse\Service\TimeLapseCreatorService;
use Reifinger\Business\ImageStreamTimelapse\Service\VideoCreatorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
        name: 'timelapse:create',
        description: 'Creates a timelapse from images in the input folder'
)]
class CreateTimeLapseCommand extends Command
{
    public function __construct(
            private readonly TimeLapseCreatorService $timeLapseCreatorService,
            private readonly VideoCreatorService     $videoCreatorService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('inputFolder', InputArgument::REQUIRED, 'Input folder containing images')
                ->addArgument('outputFolder', InputArgument::REQUIRED, 'Output folder for timelapse frames')
                ->addOption('speedUp', null, InputOption::VALUE_OPTIONAL, 'Speed-up factor', 1440);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputFolder = $input->getArgument('inputFolder');
        $outputFolder = $input->getArgument('outputFolder');
        $speedUp = (float)$input->getOption('speedUp');

        $this->timeLapseCreatorService->createTimelapse($inputFolder, $outputFolder . '/tmp', $speedUp);
        $this->videoCreatorService->generateVideo($outputFolder . '/tmp', $outputFolder . '/timelapse_' . date('Y-m-d') . '.mp4', 30);
        return Command::SUCCESS;
    }
}
