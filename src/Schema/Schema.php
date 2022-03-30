<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class Schema
{
    public function __construct(
        private ?string $title,
        private Value   $value,
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getValue(): Value
    {
        return $this->value;
    }
}
