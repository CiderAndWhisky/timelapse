<?php /** @noinspection PhpMissingFieldTypeInspection */

declare(strict_types=1);

namespace Reifinger\Timelapse\Command;

use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Reader\ConfigReader;
use Reifinger\Timelapse\Service\ConsoleOutputService;
use Reifinger\Timelapse\Service\PathService;
use Reifinger\Timelapse\Service\TimelapseService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TimelapseGeneratorCommand extends Command
{
    protected static $defaultName = 'timelapse:generate';
    private ConfigReader $configReader;
    private TimelapseService $timelapseService;
    private Config $config;
    private PathService $pathService;

    public function __construct(
            Config $config,
            ConfigReader $configReader,
            TimelapseService $timelapseService,
            PathService $imagePathService
    ) {
        parent::__construct();
        $this->addArgument('config', InputArgument::REQUIRED);
        $this->addOption('force');
        $this->addOption('keepTempImages');
        $this->configReader = $configReader;
        $this->timelapseService = $timelapseService;
        $this->config = $config;
        $this->pathService = $imagePathService;
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputService = new ConsoleOutputService($input, $output);

        $outputService->write('Reading Config');
        $configFile = $input->getArgument('config');
        if (!$configFile || is_array($configFile)) {
            $outputService->error('No or multiple config files given!');

            return 1;
        }
        $this->configReader->applyTo($this->config, $configFile);

        $targetPath = $this->pathService->getOutputPath();
        $outputService->write('Creating target folder '.$targetPath);

        $this->timelapseService->force = $input->getOption('force') !== null;
        $this->timelapseService->keepTempImages = $input->getOption('keepTempImages') !== null;

        $this->timelapseService->createTimelapse($outputService);

        return 0;
    }
}
