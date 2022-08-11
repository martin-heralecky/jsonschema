<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<mixed>
 */
class MixedSchema extends Schema
{
    /**
     * @param Value<mixed>|null $default
     * @param mixed[]           $examples
     * @param mixed[]           $enumValues
     */
    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?Value  $default = null,
        array   $examples = [],
        array   $enumValues = [],
    ) {
        parent::__construct($title, $description, $default, $examples, $enumValues);
    }
}
