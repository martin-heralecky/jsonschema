<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

abstract class Value
{
    public function __construct(
        private ?string $description = null,
    ) {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
