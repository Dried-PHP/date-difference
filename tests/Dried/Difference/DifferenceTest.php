<?php

declare(strict_types=1);

namespace Tests\Dried\Difference;

use DateInterval;
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
        ];
    }

    #[DataProvider('getToMicrosecondsCases')]
    public function testToMicroseconds(float $result, string $from, string $to): void
    {
        $difference = new Difference(new DateTimeImmutable($from), new DateTimeImmutable($to));

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
        $difference = new Difference(new DateTimeImmutable($from), new DateTimeImmutable($to));
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

        new Difference(
            new DateTimeImmutable('now America/New_York'),
            new DateTimeImmutable('now Europe/Amsterdam'),
        );
    }
}
