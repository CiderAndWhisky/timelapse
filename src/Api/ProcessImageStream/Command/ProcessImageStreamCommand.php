<?php
declare(strict_types=1);

namespace Reifinger\Api\ProcessImageStream\Command;

use Reifinger\Business\ProcessImageStream\Service\ImageStreamProcessorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
        name: 'timelapse:image-stream:process',
        description: 'Takes all images downloaded to the image stream folder and moves them to the output folder if there is significant change in the image.'
)]
class ProcessImageStreamCommand extends Command
{
    public function __construct(private readonly ImageStreamProcessorService $imageStreamProcessor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('imageStreamFolder', InputArgument::REQUIRED, 'The image stream folder.')
                ->addArgument('outputFolder', InputArgument::REQUIRED, 'The output folder.')
                ->addOption('threshold', 't', InputOption::VALUE_OPTIONAL, 'The threshold for image differences.', 0.0005);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $imageStreamFolder = $input->getArgument('imageStreamFolder');
        $outputFolder = $input->getArgument('outputFolder');
        $threshold = (float)$input->getOption('threshold');

        $this->imageStreamProcessor->process($imageStreamFolder, $outputFolder, $threshold);

        return Command::SUCCESS;
    }
}
