<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Type
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
