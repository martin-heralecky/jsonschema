<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Cast;

use MartinHeralecky\Jsonschema\Exception\CastException;

interface CastJsonToPhp
{
    /**
     * @throws CastException
     */
    public function jsonToPhp(mixed $json): mixed;
}
