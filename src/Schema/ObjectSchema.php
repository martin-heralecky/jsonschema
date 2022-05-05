<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

/**
 * @todo default, examples, enumValues
 */
class ObjectSchema extends Schema
{
    /**
     * @param ObjectSchemaProperty[] $properties
     */
    public function __construct(
        ?string       $title = null,
        ?string       $description = null,
        private array $properties = [],
    ) {
        parent::__construct($title, $description);
    }

    /**
     * @return ObjectSchemaProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
