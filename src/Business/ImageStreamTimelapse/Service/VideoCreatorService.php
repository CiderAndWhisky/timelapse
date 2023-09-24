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
        if (file_exists($outputVideoFile)) {
            unlink($outputVideoFile);
        }
        shell_exec(sprintf(
                'ffmpeg -framerate %d -pattern_type glob -i \'%s/*.jpg\' -c:v libx264 %s',
                $frameRate,
                $imageFolder,
                $outputVideoFile
        ));
        //Delete the images
        shell_exec(sprintf('rm %s/*.jpg', $imageFolder));
    }
}
