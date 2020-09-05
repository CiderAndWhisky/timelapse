<?php

declare(strict_types=1);

namespace Reifinger\Timelapse\Model;

class Vector2D
{
    public float $x;
    public float $y;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function equals(Vector2D $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }
}
