<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Enum
{
    public function __construct(
        private readonly array $values,
    ) {
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
