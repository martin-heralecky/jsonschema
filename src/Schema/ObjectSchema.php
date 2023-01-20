<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Value;

class ObjectSchema extends Schema
{
    /**
     * @param class-string|null $phpClass
     * @param ObjectSchemaProperty[] $properties
     */
    public function __construct(
        ?string $title = null,
        ?string $description = null,
        private readonly ?string $phpClass = null,
        private readonly array $properties = [],
        ?Value $default = null,
        array $examples = [],
        array $enumValues = [],
        ?JsonToPhpCast $jsonToPhpCast = null,
        ?PhpToJsonCast $phpToJsonCast = null,
    ) {
        parent::__construct($title, $description, $default, $examples, $enumValues, $jsonToPhpCast, $phpToJsonCast);
    }

    /**
     * @return class-string|null
     */
    public function getPhpClass(): ?string
    {
        return $this->phpClass;
    }

    /**
     * @return ObjectSchemaProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
