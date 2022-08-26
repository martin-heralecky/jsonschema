<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Cast;

use MartinHeralecky\Jsonschema\Exception\CastException;

interface PhpToJsonCast
{
    /**
     * @throws CastException
     */
    public function phpToJson(mixed $php): mixed;
}
