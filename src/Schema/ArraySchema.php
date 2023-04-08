<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Value;

/**
 * @template TItem
 * @extends Schema<array<int, mixed>>
 */
class ArraySchema extends Schema
{
    /**
     * @param Schema<TItem> $itemSchema
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
        private readonly int $minItems = 0,
    ) {
        parent::__construct($title, $description, $default, $examples, $enumValues, $jsonToPhpCast, $phpToJsonCast);
    }

    /**
     * @return Schema<TItem>
     */
    public function getItemSchema(): Schema
    {
        return $this->itemSchema;
    }

    public function getMinItems(): int
    {
        return $this->minItems;
    }
}
