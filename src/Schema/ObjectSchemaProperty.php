<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class ObjectSchemaProperty
{
    // ve skutecnosti nepotrbuju phpName protoze v Instantiatoru muzu zase projit anotace. mozna je to ale blbost,
    // protoze nechci anotace prochazet dvakrat a navic schema mohlo byt vytvoreno jinak nez anotacema.
    // todo zamyslet se jestli je phpName potreba
    public function __construct(
        private string $name,
        private string $phpName,
        private Schema $schema,
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
