<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength
{
    public function __construct(
        private readonly int $value,
    ) {
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
