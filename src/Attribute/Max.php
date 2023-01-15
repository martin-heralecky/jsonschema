<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max
{
    public function __construct(
        private readonly int|float $value,
    ) {
    }

    public function getValue(): int|float
    {
        return $this->value;
    }
}
