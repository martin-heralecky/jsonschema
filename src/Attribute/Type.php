<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Type
{
    public function __construct(
        private string $type,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }
}
