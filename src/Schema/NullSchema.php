<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<null>
 */
class NullSchema extends Schema
{
    /**
     * @param Value<null>|null $default
     * @param null[]           $examples
     * @param null[]           $enumValues
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
