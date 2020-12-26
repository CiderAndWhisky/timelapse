<?php /** @noinspection PhpMissingFieldTypeInspection */

declare(strict_types=1);

namespace Reifinger\Timelapse\Command;

use Reifinger\Timelapse\Model\Config;
use Reifinger\Timelapse\Model\RenderImageInformation;
use Reifinger\Timelapse\Reader\ConfigReader;
use Reifinger\Timelapse\Service\ImageWriterService;
use Reifinger\Timelapse\Service\RendererService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FrameRenderCommand extends Command
{
    protected static $defaultName = 'frame:render';
    private RendererService $rendererService;
    private ImageWriterService $imageWriterService;
    private ConfigReader $configReader;
    /** @var Config */
    private Config $config;

    public function __construct(
            Config $config,
            RendererService $rendererService,
            ImageWriterService $imageWriterService,
            ConfigReader $configReader)
    {
        parent::__construct();
        $this->rendererService = $rendererService;
        $this->imageWriterService = $imageWriterService;
        $this->configReader = $configReader;
        $this->config = $config;
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function configure()
    {
        parent::configure();
        $this->addArgument('config', InputArgument::REQUIRED);
        $this->addArgument('renderInfo', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $input->getArgument('config');
        if (!$configFile || is_array($configFile)) {
            return 1;
        }
        $this->configReader->applyTo($this->config, $configFile);

        /** @var RenderImageInformation $renderImageInformation */
        $renderImageInformation = unserialize($input->getArgument('renderInfo'), [RenderImageInformation::class]);
        $frame = $this->rendererService->renderImage($renderImageInformation);
        $this->imageWriterService->writeImage(
                $frame,
                $renderImageInformation->frameNumber,
                $renderImageInformation->targetPath
        );

        return 0;
    }
}
