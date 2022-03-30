<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class ObjectValueProperty
{
    public function __construct(
        private string $name,
        private bool   $required,
        private Value  $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getValue(): Value
    {
        return $this->value;
    }
}
