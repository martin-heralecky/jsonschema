<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema;

interface Caster
{
    public function jsonToPhp(mixed $json): mixed;

    public function phpToJson(mixed $php): mixed;
}
