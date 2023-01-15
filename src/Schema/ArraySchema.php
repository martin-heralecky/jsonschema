<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<array<int, mixed>>
 */
class ArraySchema extends Schema
{
    /**
     * @param Value<array<int, mixed>>|null $default
     * @param array<int, mixed>[] $examples
     * @param array<int, mixed>[] $enumValues
     */
    public function __construct(
        private readonly Schema $itemSchema,
        ?string $title = null,
        ?string $description = null,
        ?Value $default = null,
        array $examples = [],
        array $enumValues = [],
        ?JsonToPhpCast $jsonToPhpCast = null,
        ?PhpToJsonCast $phpToJsonCast = null,
    ) {
        parent::__construct($title, $description, $default, $examples, $enumValues, $jsonToPhpCast, $phpToJsonCast);
    }

    public function getItemSchema(): Schema
    {
        return $this->itemSchema;
    }
}
