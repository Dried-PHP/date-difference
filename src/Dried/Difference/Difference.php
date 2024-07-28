<?php

declare(strict_types=1);

namespace Dried\Difference;

use DateInterval;
use DateTimeImmutable;
use Dried\Difference\Exception\TimezoneMismatch;

final class Difference
{
    private DateInterval $interval;
    private float $microseconds;

    public function __construct(
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {
        $fromTimeZone = $this->from->getTimezone();
        $toTimeZone = $this->to->getTimezone();

        if ($fromTimeZone === false || $toTimeZone === false || $fromTimeZone->getName() !== $toTimeZone->getName()) {
            throw TimezoneMismatch::fromTimezones($fromTimeZone ?: null, $toTimeZone ?: null);
        }
    }

    public function toInterval(bool $absolute = false): DateInterval
    {
        return $this->interval ??= $this->from->diff($this->to, $absolute);
    }

    public function toMicroseconds(): float
    {
        return $this->microseconds ??= ($this->to->getTimestamp() - $this->from->getTimestamp()) * 1_000_000.0
            + ((float) $this->to->format('u') - (float) $this->from->format('u'));
    }
}
