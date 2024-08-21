<?php

declare(strict_types=1);

namespace Dried\Difference;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Dried\Difference\Builder\FromBuilder;
use Dried\Difference\Builder\ToBuilder;
use Dried\Difference\Exception\TimezoneMismatch;
use Dried\Utils\Rounding;
use Dried\Utils\RoundingMode;
use Dried\Utils\Unit;
use Dried\Utils\UnitAmount;
use InvalidArgumentException;

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

    public static function weeks(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toWeeks();
    }

    public static function months(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toMonths();
    }

    public static function quarters(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toQuarters();
    }

    public static function years(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toYears();
    }

    public static function decades(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toDecades();
    }

    public static function centuries(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toCenturies();
    }

    public static function millennia(DateTimeInterface $from, DateTimeInterface $to): float
    {
        return self::between($from, $to)->toMillennia();
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

    public function toWeeks(): float
    {
        return $this->toDays() / 7;
    }

    public function toMonths(): float
    {
        $start = $this->from;
        $end = $this->to;
        [$yearStart, $monthStart, $dayStart] = explode('-', $start->format('Y-m-dHisu'));
        [$yearEnd, $monthEnd, $dayEnd] = explode('-', $end->format('Y-m-dHisu'));

        $monthsDiff = (((int) $yearEnd) - ((int) $yearStart)) * 12 +
            ((int) $monthEnd) - ((int) $monthStart);

        if ($monthsDiff > 0) {
            $monthsDiff -= ($dayStart > $dayEnd ? 1 : 0);
        } elseif ($monthsDiff < 0) {
            $monthsDiff += ($dayStart < $dayEnd ? 1 : 0);
        }

        $ascending = ($start <= $end);
        $sign = $ascending ? 1 : -1;
        $monthsDiff = abs($monthsDiff);

        if (!$ascending) {
            [$start, $end] = [$end, $start];
        }

        $floorEnd = $start->modify("$monthsDiff months");

        if ($floorEnd >= $end) {
            return $sign * $monthsDiff;
        }

        $ceilEnd = $start->modify(($monthsDiff + 1) . ' months');

        $daysToFloor = self::days($floorEnd, $end);
        $daysToCeil = self::days($end, $ceilEnd);

        return $sign * ($monthsDiff + $daysToFloor / ($daysToCeil + $daysToFloor));
    }

    public function toQuarters(): float
    {
        return $this->toMonths() / 3;
    }

    public function toYears(): float
    {
        $start = $this->from;
        $end = $this->to;
        $ascending = ($start <= $end);
        $sign = $ascending ? 1 : -1;

        if (!$ascending) {
            [$start, $end] = [$end, $start];
        }

        $yearsDiff = (int) $this->toInterval()->format('%y');
        $floorEnd = $start->modify("$yearsDiff years");

        if ($floorEnd >= $end) {
            return $sign * $yearsDiff;
        }

        $ceilEnd = $start->modify(($yearsDiff + 1) . ' years');

        $daysToFloor = self::days($floorEnd, $end);
        $daysToCeil = self::days($end, $ceilEnd);

        return $sign * ($yearsDiff + $daysToFloor / ($daysToCeil + $daysToFloor));
    }

    public function toDecades(): float
    {
        return $this->toYears() / 10;
    }

    public function toCenturies(): float
    {
        return $this->toYears() / 100;
    }

    public function toMillennia(): float
    {
        return $this->toYears() / 1000;
    }

    public function toUnit(Unit $unit): float
    {
        return match ($unit) {
            Unit::Microsecond => $this->toMicroseconds(),
            Unit::Millisecond => $this->toMilliseconds(),
            Unit::Second => $this->toSeconds(),
            Unit::Minute => $this->toMinutes(),
            Unit::Hour => $this->toHours(),
            Unit::Day => $this->toDays(),
            Unit::Week => $this->toWeeks(),
            Unit::Month => $this->toMonths(),
            Unit::Quarter => $this->toQuarters(),
            Unit::Year => $this->toYears(),
            Unit::Decade => $this->toDecades(),
            Unit::Century => $this->toCenturies(),
            Unit::Millennium => $this->toMillennia(),
        };
    }

    /**
     * @param list<Unit> $units
     *
     * @return list<UnitAmount>
     */
    public function toUnits(array $units): array
    {
        foreach ($units as $unit) {
            if (!$unit instanceof Unit) {
                throw new InvalidArgumentException(
                    (\is_object($unit) ? $unit::class : \gettype($unit)) .
                    ' is not a valid Unit enum value.'
                );
            }
        }

        $rounding = new Rounding();
        $amounts = [];
        $difference = $this;

        /** @var Unit $unit */
        foreach (array_reverse(Unit::cases()) as $unit) {
            if (!\in_array($unit, $units, true)) {
                continue;
            }

            $amount = $rounding->roundInteger($difference->toUnit($unit), RoundingMode::ClosestToZero);
            $amounts[] = new UnitAmount($unit, $amount);
            $difference = self::between(
                $difference->from->modify($unit->modifier($amount)),
                $difference->to,
            );
        }

        return $amounts;
    }

    private function calculateDays(): float
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
