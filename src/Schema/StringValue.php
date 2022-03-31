<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

class StringValue extends Value
{
    /**
     * @param string[] $examples
     */
    public function __construct(
        ?string         $description = null,
        private ?string $default = null,
        private array   $examples = [],
        private ?string $pattern = null,
    ) {
        parent::__construct($description);
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    /**
     * @return string[]
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }
}
