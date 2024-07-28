<?php

declare(strict_types=1);

namespace Tests\Dried\Difference\Exception;

use DateTimeZone;
use Dried\Difference\Exception\TimezoneMismatch;
use PHPUnit\Framework\TestCase;

class TimezoneMismatchTest extends TestCase
{
    public function testMessage(): void
    {
        $mismatch = TimezoneMismatch::fromTimezones(
            new DateTimeZone('America/New_York'),
            new DateTimeZone('Europe/Amsterdam'),
        );

        self::assertSame(
            'Unable to reliably calculate a date difference between dates not being on the same timezone,' .
            " received DateTimeZone('America/New_York') and DateTimeZone('Europe/Amsterdam').\n" .
            "You can convert the second date with \$to->setTimezone(new DateTimeZone('America/New_York'))",
            $mismatch->getMessage(),
        );

        $mismatch = TimezoneMismatch::fromTimezones(
            new DateTimeZone('America/New_York'),
            null,
        );

        self::assertSame(
            'Unable to reliably calculate a date difference between dates not being on the same timezone,' .
            " received DateTimeZone('America/New_York') and null.",
            $mismatch->getMessage(),
        );

        $mismatch = TimezoneMismatch::fromTimezones(false, false);

        self::assertSame(
            'Unable to reliably calculate a date difference between dates not being on the same timezone,' .
            " received false and false.",
            $mismatch->getMessage(),
        );
    }
}
