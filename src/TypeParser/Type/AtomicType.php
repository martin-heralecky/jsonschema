<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\TypeParser\Type;

/**
 * @internal
 */
class AtomicType implements Type
{
    /**
     * @param Type[] $genericTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly array $genericTypes = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Type[]
     */
    public function getGenericTypes(): array
    {
        return $this->genericTypes;
    }
}
