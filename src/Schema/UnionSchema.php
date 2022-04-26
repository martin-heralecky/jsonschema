<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<mixed>
 */
class UnionSchema extends Schema
{
    /**
     * @param Schema<mixed>[]   $schemas
     * @param Value<mixed>|null $default
     */
    public function __construct(
        private array $schemas,
        ?string       $title = null,
        ?string       $description = null,
        ?Value        $default = null,
        array         $examples = [],
    ) {
        parent::__construct($title, $description, $default, $examples);
    }

    /**
     * @return Schema<mixed>[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }
}
