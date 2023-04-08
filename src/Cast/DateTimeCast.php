<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Cast;

use Attribute;
use DateTime;
use Exception;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DateTimeCast implements JsonToPhpCast, PhpToJsonCast
{
    public function jsonToPhp(mixed $json): DateTime
    {
        if (!is_string($json)) {
            throw new CastException();
        }

        try {
            return new DateTime($json);
        } catch (Exception $e) {
            throw new CastException(previous: $e);
        }
    }

    public function phpToJson(mixed $php): string
    {
        if (!($php instanceof DateTime)) {
            throw new CastException();
        }

        return $php->format(DATE_ATOM);
    }
}
