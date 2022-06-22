<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\TypeParser\Type;

/**
 * @internal
 */
class BuiltinType implements Type
{
    public function __construct(
        private string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
