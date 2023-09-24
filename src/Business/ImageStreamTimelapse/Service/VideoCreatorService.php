<?php
declare(strict_types=1);

namespace Reifinger\Business\ImageStreamTimelapse\Service;

class VideoCreatorService
{
    public function generateVideo(string $imageFolder,
                                  string $outputVideoFile,
                                  int    $frameRate): void
    {
        $imageFolder = realpath($imageFolder);
        $command = sprintf(
                'ffmpeg -framerate %d -pattern_type glob -i \'%s/*.jpg\' -c:v libx264 %s',
                $frameRate,
                $imageFolder,
                $outputVideoFile
        );

        shell_exec($command);
        //Delete the images
        $command = sprintf('rm %s/*.jpg', $imageFolder);
        shell_exec($command);
    }
}
