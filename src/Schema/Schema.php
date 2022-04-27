<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @template T
 */
abstract class Schema
{
    /**
     * @param Value<T>|null $default
     * @param T[]           $examples
     * @param T[]           $enumValues
     */
    public function __construct(
        private ?string $title = null,
        private ?string $description = null,
        private ?Value  $default = null,
        private array   $examples = [],
        private array   $enumValues = [],
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return Value<T>|null
     */
    public function getDefault(): ?Value
    {
        return $this->default;
    }

    /**
     * @return T[]
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * @return T[]
     */
    public function getEnumValues(): array
    {
        return $this->enumValues;
    }
}
