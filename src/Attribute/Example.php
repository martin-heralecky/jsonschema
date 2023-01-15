<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Example
{
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
