<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class Value
{
    public function __construct(
        private ?string $description,
    ) {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
