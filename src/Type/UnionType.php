<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Type;

/**
 * @internal
 */
class UnionType implements Type
{
    /**
     * @param Type[] $types
     */
    public function __construct(
        private readonly array $types,
    ) {
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }
}
