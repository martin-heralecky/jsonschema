<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Cast;

use Attribute;
use DateTime;
use MartinHeralecky\Jsonschema\Exception\CastException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CastDateTime implements Cast
{
    public function jsonToPhp(mixed $json): DateTime
    {
        if (!is_string($json)) {
            throw new CastException();
        }

        return new DateTime($json);
    }

    public function phpToJson(mixed $php): string
    {
        if (!($php instanceof DateTime)) {
            throw new CastException();
        }

        return $php->format(DATE_ATOM);
    }
}
