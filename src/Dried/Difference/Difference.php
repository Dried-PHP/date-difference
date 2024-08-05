<?php

declare(strict_types=1);

namespace Dried\Difference;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Dried\Difference\Builder\FromBuilder;
use Dried\Difference\Builder\ToBuilder;
use Dried\Difference\Exception\TimezoneMismatch;

final class Difference
{
    private array $intervals = [];
    private float $microseconds;
    private float $days;

    private function __construct(
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {
        $fromTimeZone = $this->from->getTimezone();
        $toTimeZone = $this->to->getTimezone();

        if ($fromTimeZone === false || $toTimeZone === false || $fromTimeZone->getName() !== $toTimeZone->getName()) {
            throw TimezoneMismatch::fromTimezones($fromTimeZone ?: null, $toTimeZone ?: null);
        }
    }

    public static function between(DateTimeInterface $from, DateTimeInterface $to): self
    {
        return new self(
            DateTimeImmutable::createFromInterface($from),
            DateTimeImmutable::createFromInterface($to),
        );
    }

    public static function from(DateTimeInterface $date): ToBuilder
    {
        return new ToBuilder($date);
    }

    public static function to(DateTimeInterface $date): FromBuilder
    {
        return new FromBuilder($date);
    }

    public static function microseconds(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toMicroseconds();
    }

    public static function milliseconds(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toMilliseconds();
    }

    public static function seconds(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toSeconds();
    }

    public static function minutes(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toMinutes();
    }

    public static function hours(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toHours();
    }

    public static function days(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toDays();
    }

    public function toInterval(bool $absolute = false): DateInterval
    {
        return $this->intervals[$absolute ? 'absolute' : 'relative'] ??= $this->from->diff($this->to, $absolute);
    }

    public function toMicroseconds(): float
    {
        return $this->microseconds ??= ($this->to->getTimestamp() - $this->from->getTimestamp()) * 1_000_000.0
            + ((float) $this->to->format('u') - (float) $this->from->format('u'));
    }

    public function toMilliseconds(): float
    {
        return $this->toMicroseconds() / 1_000;
    }

    public function toSeconds(): float
    {
        return $this->toMicroseconds() / 1_000_000;
    }

    public function toMinutes(): float
    {
        return $this->toMicroseconds() / 60_000_000;
    }

    public function toHours(): float
    {
        return $this->toMicroseconds() / 3_600_000_000;
    }

    public function toDays(): float
    {
        return $this->days ??= $this->calculateDays();
    }

    public function calculateDays(): float
    {
        $negative = ($this->to < $this->from);
        [$start, $end] = $negative ? [$this->to, $this->from] : [$this->from, $this->to];
        $interval = $this->toInterval(true);
        $daysA = (int) $interval->format('%r%a');
        $floorEnd = $start->modify("$daysA days");
        $daysB = $daysA + ($floorEnd <= $end ? 1 : -1);
        $ceilEnd = $start->modify("$daysB days");
        $microsecondsBetween = self::microseconds($floorEnd, $ceilEnd);
        $microsecondsToEnd = self::microseconds($floorEnd, $end);

        return ($negative ? -1 : 1)
            * ($daysA * ($microsecondsBetween - $microsecondsToEnd) + $daysB * $microsecondsToEnd)
            / $microsecondsBetween;
    }
}
