<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Cast;

interface PhpToJsonCast
{
    /**
     * @throws CastException
     */
    public function phpToJson(mixed $php): mixed;
}
