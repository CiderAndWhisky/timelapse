<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Service;

class FileService
{
    public function removeDirectoryRecursive(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            if ($objects !== false) {
                foreach ($objects as $object) {
                    if ($object !== "." && $object !== "..") {
                        if (is_dir($dir.DIRECTORY_SEPARATOR.$object) && !is_link($dir."/".$object)) {
                            $this->removeDirectoryRecursive($dir.DIRECTORY_SEPARATOR.$object);
                        } else {
                            unlink($dir.DIRECTORY_SEPARATOR.$object);
                        }
                    }
                }
            }
            rmdir($dir);
        }
    }
}
