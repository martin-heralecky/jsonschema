<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class ObjectValue extends Value
{
    /**
     * @param ObjectValueProperty[] $properties
     */
    public function __construct(
        ?string       $description = null,
//        private       $default, todo
//        private array $examples, todo
        private array $properties = [],
    ) {
        parent::__construct($description);
    }

    /**
     * @return ObjectValueProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
