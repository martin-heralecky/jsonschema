<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\TypeParser\Type;

/**
 * @internal
 */
class UnionType implements Type
{
    /**
     * @param Type[] $types
     */
    public function __construct(
        private array $types,
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
