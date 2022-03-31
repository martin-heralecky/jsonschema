<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class IntegerValue extends Value
{
    /**
     * @param int[] $examples
     */
    public function __construct(
        ?string       $description = null,
        private ?int  $default = null,
        private array $examples = [],
        private ?int  $minimum = null,
        private ?int  $maximum = null,
    ) {
        parent::__construct($description);
    }

    public function getDefault(): ?int
    {
        return $this->default;
    }

    /**
     * @return int[]
     */
    public function getExamples(): array
    {
        return $this->examples;
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
