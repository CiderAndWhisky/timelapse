<?php
declare(strict_types=1);

namespace Reifinger\Business\ProcessImageStream\Service;

use Imagick;

class ImageComparatorService
{
    public function compare(string $imagePath1, string $imagePath2): float
    {
        $image1 = new Imagick($imagePath1);
        $image2 = new Imagick($imagePath2);

        // Compare images
        $result = $image1->compareImages($image2, Imagick::METRIC_MEANSQUAREERROR);

        $image1->clear();
        $image2->clear();

        return (float)$result[1];
    }
}
