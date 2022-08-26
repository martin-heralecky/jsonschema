<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<array<int, mixed>>
 */
class ArraySchema extends Schema
{
    /**
     * @param Value<array<int, mixed>>|null $default
     * @param array<int, mixed>[]           $examples
     * @param array<int, mixed>[]           $enumValues
     */
    public function __construct(
        private Schema $itemSchema,
        ?string $title = null,
        ?string $description = null,
        ?Value $default = null,
        array $examples = [],
        array $enumValues = [],
    ) {
        parent::__construct($title, $description, $default, $examples, $enumValues);
    }

    public function getItemSchema(): Schema
    {
        return $this->itemSchema;
    }
}
