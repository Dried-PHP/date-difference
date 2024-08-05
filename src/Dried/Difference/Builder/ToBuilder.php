<?php

declare(strict_types=1);

namespace Dried\Difference\Builder;

use DateTimeInterface;
use Dried\Difference\Difference;

final readonly class ToBuilder
{
    public function __construct(
        private DateTimeInterface $from,
    ) {
    }

    public function to(DateTimeInterface $date): Difference
    {
        return Difference::between($this->from, $date);
    }
}
