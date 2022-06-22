<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<bool>
 */
class BooleanSchema extends Schema
{
    /**
     * @param Value<bool>|null $default
     * @param bool[]           $examples
     * @param bool[]           $enumValues
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
