<?php

declare(strict_types=1);

namespace Tests\Dried\Difference;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Dried\Difference\Difference;
use Dried\Difference\Exception\TimezoneMismatch;
use Dried\Utils\Unit;
use Dried\Utils\UnitAmount;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DifferenceTest extends TestCase
{
    public static function getToMicrosecondsCases(): array
    {
        return [
            [
                ((16 - 15) * 60 + (34 - 26)) * 1_000_000 + (456789 - 123456),
                '2024-07-28 14:15:26.123456',
                '2024-07-28 14:16:34.456789',
            ],
            [
                6 * 3_600_000_000,
                '2024-07-28 14:15:26',
                '2024-07-28 20:15:26',
            ],
            [
                24.0 * 60 * 60 * 1000 * 1000,
                '2024-11-03 01:24:22.848816 UTC',
                '2024-11-04 01:24:22.848816 UTC',
            ],
        ];
    }

    #[DataProvider('getToMicrosecondsCases')]
    public function testToMicroseconds(float $result, string $from, string $to): void
    {
        $difference = Difference::between(new DateTimeImmutable($from), new DateTimeImmutable($to));

        self::assertSame($result, $difference->toMicroseconds());
    }

    public static function getToIntervalCases(): array
    {
        $interval = new DateInterval('PT1M8S');
        $interval->f = 0.456789 - 0.123456;

        return [
            [
                $interval,
                '2024-07-28 14:15:26.123456',
                '2024-07-28 14:16:34.456789',
            ],
            [
                new DateInterval('P1M2D'),
                '2024-07-28',
                '2024-08-30',
            ],
        ];
    }

    #[DataProvider('getToIntervalCases')]
    public function testToInterval(DateInterval $interval, string $from, string $to): void
    {
        $difference = Difference::between(new DateTimeImmutable($from), new DateTimeImmutable($to));
        $result = $difference->toInterval();

        self::assertInstanceOf(DateInterval::class, $result);
        self::assertSame($interval->format('%R %y %m %d %H %i %s %f'), $result->format('%R %y %m %d %H %i %s %f'));
    }

    public function testTimezoneMismatch(): void
    {
        self::expectExceptionObject(
            TimezoneMismatch::fromTimezones(
                new DateTimeZone('America/New_York'),
                new DateTimeZone('Europe/Amsterdam'),
            ),
        );

        Difference::between(
            new DateTimeImmutable('now America/New_York'),
            new DateTimeImmutable('now Europe/Amsterdam'),
        );
    }

    public function testToMillennia(): void
    {
        $first = new DateTime('2023-12-01 00:00:00.0 Africa/Nairobi');
        $second = new DateTime('3523-12-01 00:00:00.0 Africa/Nairobi');

        $this->assertSame(1.5, Difference::millennia($first, $second));
        $this->assertSame(-1.5, Difference::millennia($second, $first));
    }

    public function testToCenturies(): void
    {
        $first = new DateTime('2023-12-01 00:00:00.0 Africa/Nairobi');
        $second = new DateTime('2173-12-01 00:00:00.0 Africa/Nairobi');

        $this->assertSame(1.5, Difference::centuries($first, $second));
        $this->assertSame(-1.5, Difference::centuries($second, $first));
    }

    public function testToDecades(): void
    {
        $first = new DateTime('2023-12-01 00:00:00.0 Africa/Nairobi');
        $second = new DateTime('2038-12-01 00:00:00.0 Africa/Nairobi');

        $this->assertSame(1.5, Difference::decades($first, $second));
        $this->assertSame(-1.5, Difference::decades($second, $first));
    }

    public function testToYears(): void
    {
        $first = new DateTime('2023-12-01 00:00:00.0 Africa/Nairobi');
        $second = new DateTime('2025-04-15 12:00:00.0 Africa/Nairobi');

        $this->assertSame(1 + 135.5 / 365, Difference::years($first, $second));
        $this->assertSame(-(1 + 135.5 / 365), Difference::years($second, $first));
    }

    public function testToQuarters(): void
    {
        $first = new DateTime('2023-12-01 00:00:00.0 Africa/Nairobi');
        $second = new DateTime('2024-04-01 00:00:00.0 Africa/Nairobi');

        $this->assertSame(4 / 3, Difference::quarters($first, $second));
        $this->assertSame(-4 / 3, Difference::quarters($second, $first));
    }

    public function testToMonths(): void
    {
        $first = new DateTime('2022-12-01 00:00:00.0 Africa/Nairobi');
        $second = new DateTime('2022-11-01 00:00:00.0 Africa/Nairobi');

        $this->assertSame(-1.0, Difference::between($first, $second)->toMonths());
        $this->assertSame(1.0, Difference::between($second, $first)->toMonths());

        $first = new DateTime('2022-02-01 16:00 America/Toronto');
        $second = new DateTime('2022-01-01 20:00 Europe/Berlin');
        $second->setTimezone(new DateTimeZone('America/Toronto'));

        $this->assertEqualsWithDelta(-1.0029761904761905, Difference::months($first, $second), 0.00000001);
        $this->assertEqualsWithDelta(1.0029761904761905, Difference::months($second, $first), 0.00000001);

        $first = new DateTime('2022-02-01 01:00 America/Toronto');
        $second = new DateTime('2022-01-01 00:00 Europe/Berlin');
        $second->setTimezone(new DateTimeZone('America/Toronto'));

        $this->assertEqualsWithDelta(-(1 + 7 / 24 / 31), Difference::months($first, $second), 0.00000001);
        // $second date in Toronto is 2021-12-31 18:00, so we have 6 hours in December (a 31 days month), and 1 hour in February (28 days month)
        $this->assertEqualsWithDelta(1 + 7 / 24 / 31, Difference::months($second, $first), 0.00000001);
        // Considered in Berlin timezone, the 7 extra hours are in February (28 days month)

        $first = new DateTime('2022-02-01 01:00 Europe/Berlin');
        $second = new DateTime('2022-01-01 00:00 America/Toronto');
        $second->setTimezone(new DateTimeZone('Europe/Berlin'));

        $this->assertEqualsWithDelta(-(1 - 5 / 24 / 31), Difference::months($first, $second), 0.00000001);
        $this->assertEqualsWithDelta(1 - 5 / 24 / 31, Difference::months($second, $first), 0.00000001);
    }

    public function testToWeeks(): void
    {
        $start = new DateTimeImmutable('2030-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2027-05-02 01:24:22.848816 UTC');

        $this->assertSame(-1281.0 / 7, Difference::from($start)->to($end)->toWeeks());

        $start = new DateTime('2030-11-03 01:24:22.848816 America/Toronto');
        $end = new DateTime('2027-05-02 01:24:22.848816 America/Toronto');

        $this->assertSame(-1281.0 / 7, Difference::to($end)->from($start)->toWeeks());

        $start = new class ('2030-11-03 01:24:22.848816 America/Toronto') extends DateTime {};
        $end = new class ('2027-05-02 01:24:22.848816 America/Toronto') extends DateTime {};

        $this->assertSame(-1281.0 / 7, Difference::weeks($start, $end));
    }

    public function testToDays(): void
    {
        $start = new DateTimeImmutable('2030-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2027-05-02 01:24:22.848816 UTC');

        $this->assertSame(-1281.0, Difference::from($start)->to($end)->toDays());

        $start = new DateTime('2030-11-03 01:24:22.848816 America/Toronto');
        $end = new DateTime('2027-05-02 01:24:22.848816 America/Toronto');

        $this->assertSame(-1281.0, Difference::to($end)->from($start)->toDays());

        $start = new class ('2030-11-03 01:24:22.848816 America/Toronto') extends DateTime {};
        $end = new class ('2027-05-02 01:24:22.848816 America/Toronto') extends DateTime {};

        $this->assertSame(-1281.0, Difference::days($start, $end));
    }

    public function testToHours(): void
    {
        $start = new DateTimeImmutable('2024-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2024-11-04 01:24:22.848816 UTC');

        $this->assertSame(24.0, Difference::from($start)->to($end)->toHours());

        $start = new DateTime('2024-11-03 01:24:22.848816 America/Toronto');
        $end = new DateTime('2024-11-04 01:24:22.848816 America/Toronto');

        $this->assertSame(25.0, Difference::to($end)->from($start)->toHours());

        $start = new class ('2024-11-03 01:24:22.848816 America/Toronto') extends DateTime {};
        $end = new class ('2024-11-04 01:24:22.848816 America/Toronto') extends DateTime {};

        $this->assertSame(25.0, Difference::hours($start, $end));
    }

    public function testToMinutes(): void
    {
        $start = new DateTimeImmutable('2024-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2024-11-04 01:24:22.848816 UTC');

        $this->assertSame(24.0 * 60, Difference::minutes($start, $end));
    }

    public function testToSeconds(): void
    {
        $start = new DateTimeImmutable('2024-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2024-11-04 01:24:22.848816 UTC');

        $this->assertSame(24.0 * 60 * 60, Difference::seconds($start, $end));
    }

    public function testToMilliseconds(): void
    {
        $start = new DateTimeImmutable('2024-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2024-11-04 01:24:22.848816 UTC');

        $this->assertSame(24.0 * 60 * 60 * 1000, Difference::milliseconds($start, $end));
    }

    public function testToUnits(): void
    {
        $start = new DateTimeImmutable('2024-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2027-08-14 14:13:50.012455 UTC');

        self::assertSame([
            '0 millennium',
            '0 century',
            '0 decade',
            '2 year',
            '3 quarter',
            '0 month',
            '1 week',
            '4 day',
            '12 hour',
            '49 minute',
            '27 second',
            '163 millisecond',
            '639 microsecond',
        ], array_map(
            static fn (UnitAmount $unitAmount): string => $unitAmount->amount . ' ' . $unitAmount->unit->value,
            Difference::between($start, $end)->toUnits(Unit::cases()),
        ));

        self::assertSame([
            '2 year',
            '9 month',
            '11 day',
            '12 hour',
            '49 minute',
            '27 second',
        ], array_map(
            static fn (UnitAmount $unitAmount): string => $unitAmount->amount . ' ' . $unitAmount->unit->value,
            Difference::between($start, $end)->toUnits([
                Unit::Year,
                Unit::Month,
                Unit::Day,
                Unit::Hour,
                Unit::Minute,
                Unit::Second,
            ]),
        ));

        self::assertSame([
            '-2 year',
            '-9 month',
            '-11 day',
            '-12 hour',
            '-49 minute',
            '-27 second',
        ], array_map(
            static fn (UnitAmount $unitAmount): string => $unitAmount->amount . ' ' . $unitAmount->unit->value,
            Difference::between($end, $start)->toUnits([
                Unit::Year,
                Unit::Month,
                Unit::Day,
                Unit::Hour,
                Unit::Minute,
                Unit::Second,
            ]),
        ));
    }

    public function testToUnitWrongClass(): void
    {
        self::expectExceptionObject(new InvalidArgumentException(
            UnitAmount::class . ' is not a valid Unit enum value.',
        ));

        $start = new DateTimeImmutable('2024-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2027-08-14 14:13:50.012455 UTC');

        Difference::between($start, $end)->toUnits([UnitAmount::hours(2)]);
    }

    public function testToUnitWrongType(): void
    {
        self::expectExceptionObject(new InvalidArgumentException(
            'NULL is not a valid Unit enum value.',
        ));

        $start = new DateTimeImmutable('2024-11-03 01:24:22.848816 UTC');
        $end = new DateTimeImmutable('2027-08-14 14:13:50.012455 UTC');

        Difference::between($start, $end)->toUnits([null]);
    }
}
