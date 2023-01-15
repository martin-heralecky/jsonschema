<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<mixed>
 */
class UnionSchema extends Schema
{
    /**
     * @param Schema<mixed>[] $schemas
     */
    public function __construct(
        private readonly array $schemas,
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

    /**
     * @return Schema<mixed>[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }
}
