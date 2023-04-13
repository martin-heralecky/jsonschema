<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<string>
 */
class StringSchema extends Schema
{
    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?Value $default = null,
        array $examples = [],
        array $enumValues = [],
        ?JsonToPhpCast $jsonToPhpCast = null,
        ?PhpToJsonCast $phpToJsonCast = null,
        private readonly int $minLength = 0,
        private readonly ?string $pattern = null,
    ) {
        parent::__construct($title, $description, $default, $examples, $enumValues, $jsonToPhpCast, $phpToJsonCast);
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }
}
