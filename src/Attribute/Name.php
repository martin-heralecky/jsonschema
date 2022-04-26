<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Name
{
    public function __construct(
        private string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
