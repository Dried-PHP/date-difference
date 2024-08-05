<?php

declare(strict_types=1);

namespace Tests\Dried\Difference;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Dried\Difference\Difference;
use Dried\Difference\Exception\TimezoneMismatch;
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
}
