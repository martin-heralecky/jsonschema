<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Cast;

interface JsonToPhpCast
{
    /**
     * @throws CastException
     */
    public function jsonToPhp(mixed $json): mixed;
}
