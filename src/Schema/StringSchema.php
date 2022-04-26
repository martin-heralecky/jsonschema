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
     */
    public function __construct(
        ?string         $title = null,
        ?string         $description = null,
        ?Value          $default = null,
        array           $examples = [],
        private ?string $pattern = null,
    ) {
        parent::__construct($title, $description, $default, $examples);
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }
}
