<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Value;

/**
 * @extends Schema<int>
 */
class IntegerSchema extends Schema
{
    /**
     * @param Value<int>|null $default
     * @param int[]           $examples
     */
    public function __construct(
        ?string      $title = null,
        ?string      $description = null,
        ?Value       $default = null,
        array        $examples = [],
        private ?int $minimum = null,
        private ?int $maximum = null,
    ) {
        parent::__construct($title, $description, $default, $examples);
    }

    public function getMinimum(): ?int
    {
        return $this->minimum;
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }
}
