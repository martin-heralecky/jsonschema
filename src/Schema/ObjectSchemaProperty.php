<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

class ObjectSchemaProperty
{
    public function __construct(
        private readonly string $name,
        private readonly string $phpName,
        private readonly Schema $schema,
        // private ?string $title = null, // ma tu byt, nebo jen na objektu/scalaru?
        private ?string $description = null,
        private ?Value $default = null, // mozna nedava smysl, vic DefaultValue class comment
        private array $examples = [],
        private array $enumValues = [],
        // todo asi ostatni validace?
        // todo casts

        // a ano, jsou takhle trochu "zduplikovany" tady na property a na Schema.
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
