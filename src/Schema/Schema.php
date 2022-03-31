<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class Schema
{
    public function __construct(
        private Value   $value,
        private ?string $title = null,
    ) {
    }

    public function getValue(): Value
    {
        return $this->value;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
