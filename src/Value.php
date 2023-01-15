<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema;

/**
 * Wrapper for any value. Useful in situations, in which `null` is treated both as a legal value and as an indicator
 * that something doesn't exist.
 *
 * @template T
 */
class Value
{
    /**
     * @param T $value
     */
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    /**
     * @return T
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
