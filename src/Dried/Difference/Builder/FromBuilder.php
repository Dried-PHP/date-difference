<?php

declare(strict_types=1);

namespace Dried\Difference\Builder;

use DateTimeInterface;
use Dried\Difference\Difference;

final readonly class FromBuilder
{
    public function __construct(
        private DateTimeInterface $to,
    ) {
    }

    public function from(DateTimeInterface $date): Difference
    {
        return Difference::between($date, $this->to);
    }
}
