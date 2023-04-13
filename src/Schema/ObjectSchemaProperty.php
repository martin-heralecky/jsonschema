<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Value;

class ObjectSchemaProperty
{
    public function __construct(
        private readonly string $name,
        private readonly string $phpName,
        private readonly Schema $schema,
        private readonly ?string $description = null,
        private readonly ?Value $default = null,
        private readonly array $examples = [],
        private readonly array $enumValues = [],
        private readonly ?JsonToPhpCast $jsonToPhpCast = null,
        private readonly ?PhpToJsonCast $phpToJsonCast = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhpName(): string
    {
        return $this->phpName;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDefault(): ?Value
    {
        return $this->default;
    }

    public function getExamples(): array
    {
        return $this->examples;
    }

    public function getEnumValues(): array
    {
        return $this->enumValues;
    }

    public function getJsonToPhpCast(): ?JsonToPhpCast
    {
        return $this->jsonToPhpCast;
    }

    public function getPhpToJsonCast(): ?PhpToJsonCast
    {
        return $this->phpToJsonCast;
    }
}
