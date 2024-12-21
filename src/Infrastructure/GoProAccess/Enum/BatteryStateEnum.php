<?php
declare(strict_types=1);

namespace Reifinger\Infrastructure\GoProAccess\Enum;

enum BatteryStateEnum: int
{
    case CHARGING = 4;
    case FULL = 3;
    case HALF_FULL = 2;
    case LOW = 1;
    case EMPTY = 0;
    case UNKNOWN = -1;
}
