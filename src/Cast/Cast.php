<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Cast;

use MartinHeralecky\Jsonschema\Exception\CastException;

interface Cast
{
    /**
     * @throws CastException
     */
    public function jsonToPhp(mixed $json): mixed;

    /**
     * @throws CastException
     */
    public function phpToJson(mixed $php): mixed;
}
