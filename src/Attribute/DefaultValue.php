<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DefaultValue
{
    public function __construct(
        private mixed $default,
    ) {
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }
}
