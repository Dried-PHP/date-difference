<?php

declare(strict_types=1);

namespace Dried\Difference\Exception;

use DateTimeZone;
use InvalidArgumentException;

class TimezoneMismatch extends InvalidArgumentException
{
    public static function fromTimezones(DateTimeZone|null|false $a, DateTimeZone|null|false $b): self
    {
        $aDump = self::dumpVariable($a);
        $bDump = self::dumpVariable($b);

        return new self(sprintf(
            'Unable to reliably calculate a date difference between dates not being on the same timezone,' .
            ' received %s and %s.' .
            ($a && $b ? sprintf("\nYou can convert the second date with \$to->setTimezone(new %s)", $aDump) : ''),
            $aDump,
            $bDump,
        ));
    }

    private static function dumpVariable(DateTimeZone|null|false $value): string
    {
        if ($value instanceof DateTimeZone) {
            return sprintf("DateTimeZone('%s')", $value->getName());
        }

        return $value === false ? 'false' : 'null';
    }
}
