<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\TypeParser\Type;

/**
 * @internal
 */
class ArrayLikeType implements Type
{
    public function __construct(
        private Type $valueType,
    ) {
    }

    public function getValueType(): Type
    {
        return $this->valueType;
    }
}
