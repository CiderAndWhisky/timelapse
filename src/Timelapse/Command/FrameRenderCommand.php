<?php /** @noinspection PhpMissingFieldTypeInspection */

declare(strict_types=1);

namespace Reifinger\Timelapse\Command;

use Reifinger\Timelapse\Model\RenderImageInformation;
use Reifinger\Timelapse\Service\ImageWriterService;
use Reifinger\Timelapse\Service\RendererService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FrameRenderCommand extends Command
{
    protected static $defaultName = 'frame:render';
    /** @var RendererService */
    private RendererService $rendererService;
    /** @var ImageWriterService */
    private ImageWriterService $imageWriterService;

    public function __construct(RendererService $rendererService, ImageWriterService $imageWriterService)
    {
        parent::__construct();
        $this->rendererService = $rendererService;
        $this->imageWriterService = $imageWriterService;
    }

    protected function configure()
    {
        parent::configure();
        $this->addArgument('renderInfo', InputArgument::REQUIRED);
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var RenderImageInformation $renderImageInformation */
        $renderImageInformation = unserialize($input->getArgument('renderInfo'), [RenderImageInformation::class]);
        $frame = $this->rendererService->generateImage($renderImageInformation);
        $this->imageWriterService->writeImage(
                $frame,
                $renderImageInformation->frameNumber,
                $renderImageInformation->targetPath
        );

        return 0;
    }
}
