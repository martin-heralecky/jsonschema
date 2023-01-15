<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema;

use InvalidArgumentException;
use MartinHeralecky\Jsonschema\Schema\BooleanSchema;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\Schema;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Schema\UnionSchema;

class JsonSchemaGenerator
{
    public function generate(Schema $schema, bool $root = true): array
    {
        $json = [];

        if ($root) {
            $json["\$schema"] = "http://json-schema.org/draft-07/schema";
        }

        if ($schema->getTitle() !== null) {
            $json["title"] = $schema->getTitle();
        }

        if ($schema->getDescription() !== null) {
            $json["description"] = $schema->getDescription();
        }

        if ($schema->getDefault() !== null) {
            $json["default"] = $schema->getDefault()->getValue();
        }

        if (count($schema->getExamples()) > 0) {
            $json["examples"] = $schema->getExamples();
        }

        if (count($schema->getEnumValues()) > 0) {
            $json["enum"] = $schema->getEnumValues();
        }

        if ($schema instanceof IntegerSchema) {
            $json += $this->generateIntegerSchema($schema);
        } elseif ($schema instanceof StringSchema) {
            $json += $this->generateStringSchema($schema);
        } elseif ($schema instanceof ObjectSchema) {
            $json += $this->generateObjectSchema($schema);
        } elseif ($schema instanceof BooleanSchema) {
            $json += $this->generateBooleanSchema($schema);
        } elseif ($schema instanceof NullSchema) {
            $json += $this->generateNullSchema($schema);
        } elseif ($schema instanceof UnionSchema) {
            $jsons = [];
            foreach ($schema->getSchemas() as $schema) {
                $jsons[] = $this->generate($schema, false);
            }

            $json["anyOf"] = $jsons;
        } else {
            throw new InvalidArgumentException("Unknown type of \$schema: " . get_class($schema));
        }

        return $json;
    }

    private function generateIntegerSchema(IntegerSchema $schema): array
    {
        $json = ["type" => "integer"];

        if ($schema->getMinimum() !== null) {
            $json["minimum"] = $schema->getMinimum();
        }

        if ($schema->getMaximum() !== null) {
            $json["maximum"] = $schema->getMaximum();
        }

        return $json;
    }

    private function generateStringSchema(StringSchema $schema): array
    {
        $json = ["type" => "string"];

        if ($schema->getPattern() !== null) {
            $json["pattern"] = $schema->getPattern();
        }

        return $json;
    }

    private function generateObjectSchema(ObjectSchema $schema): array
    {
        $json = ["type" => "object"];

        if (count($schema->getProperties()) > 0) {
            $json["properties"] = [];
            $json["required"] = [];

            foreach ($schema->getProperties() as $prop) {
                $json["properties"][$prop->getName()] = $this->generate($prop->getSchema(), false);
                if ($prop->getSchema()->getDefault() === null) {
                    $json["required"][] = $prop->getName();
                }
            }
        }

        return $json;
    }

    private function generateBooleanSchema(BooleanSchema $schema): array
    {
        $json = ["type" => "boolean"];

        return $json;
    }

    private function generateNullSchema(NullSchema $schema): array
    {
        $json = ["type" => "null"];

        return $json;
    }
}
