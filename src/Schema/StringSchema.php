<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<string>
 */
class StringSchema extends Schema
{
    /**
     * @param Value<string>|null $default
     * @param string[]           $examples
     * @param string[]           $enumValues
     */
    public function __construct(
        ?string         $title = null,
        ?string         $description = null,
        ?Value          $default = null,
        array           $examples = [],
        array           $enumValues = [],
        private ?string $pattern = null,
    ) {
        parent::__construct($title, $description, $default, $examples, $enumValues);
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }
}