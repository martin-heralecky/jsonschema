<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Schema;

use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Value;

/**
 * @template T
 * @todo Remove setters, add withs and add readonly.
 */
abstract class Schema
{
    /**
     * @param Value<T>|null $default
     * @param T[] $examples
     * @param T[] $enumValues
     */
    public function __construct(
        private ?string $title = null,
        private ?string $description = null,
        private ?Value $default = null,
        private array $examples = [],
        private array $enumValues = [],
        private ?JsonToPhpCast $jsonToPhpCast = null,
        private ?PhpToJsonCast $phpToJsonCast = null,
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return Value<T>|null
     */
    public function getDefault(): ?Value
    {
        return $this->default;
    }

    /**
     * @return T[]
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * @return T[]
     */
    public function getEnumValues(): array
    {
        return $this->enumValues;
    }

    public function getJsonToPhpCast(): ?JsonToPhpCast
    {
        return $this->jsonToPhpCast;
    }

    public function getPhpToJsonCast(): ?PhpToJsonCast
    {
        return $this->phpToJsonCast;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param Value<T>|null $default
     */
    public function setDefault(?Value $default): void
    {
        $this->default = $default;
    }

    /**
     * @param T[] $examples
     */
    public function setExamples(array $examples): void
    {
        $this->examples = $examples;
    }

    /**
     * @param T[] $enumValues
     */
    public function setEnumValues(array $enumValues): void
    {
        $this->enumValues = $enumValues;
    }

    public function setJsonToPhpCast(?JsonToPhpCast $jsonToPhpCast): void
    {
        $this->jsonToPhpCast = $jsonToPhpCast;
    }

    public function setPhpToJsonCast(?PhpToJsonCast $phpToJsonCast): void
    {
        $this->phpToJsonCast = $phpToJsonCast;
    }
}
