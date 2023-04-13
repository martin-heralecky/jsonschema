<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class ObjectSchemaProperty
{
    public function __construct(
        private readonly string $name,
        private readonly string $phpName,
        private readonly Schema $schema,
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
}
