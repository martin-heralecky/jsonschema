<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema;

use InvalidArgumentException;
use MartinHeralecky\Jsonschema\Schema\IntegerValue;
use MartinHeralecky\Jsonschema\Schema\ObjectValue;
use MartinHeralecky\Jsonschema\Schema\Schema;
use MartinHeralecky\Jsonschema\Schema\StringValue;
use MartinHeralecky\Jsonschema\Schema\Value;

class JsonSchemaGenerator
{
    public function generate(Schema $schema): array
    {
        $json = [
            "\$schema" => "http://json-schema.org/draft-07/schema",
        ];

        if ($schema->getTitle() !== null) {
            $json["title"] = $schema->getTitle();
        }

        $val  = $schema->getValue();
        $json += $this->generateValue($val);

        return $json;
    }

    private function generateValue(Value $val): array
    {
        // todo visitor?

        if ($val instanceof IntegerValue) {
            return $this->generateIntegerValue($val);
        } elseif ($val instanceof StringValue) {
            return $this->generateStringValue($val);
        } elseif ($val instanceof ObjectValue) {
            return $this->generateObjectValue($val);
        }

        throw new InvalidArgumentException("Unrecognized type of \$val: " . get_class($val));
    }

    private function generateIntegerValue(IntegerValue $val): array
    {
        $json = ["type" => "integer"];

        if ($val->getDescription() !== null) {
            $json["description"] = $val->getDescription();
        }

        if ($val->getDefault() !== null) {
            $json["default"] = $val->getDefault();
        }

        if (count($val->getExamples()) > 0) {
            $json["examples"] = [];
            foreach ($val->getExamples() as $example) {
                $json["examples"][] = $example;
            }
        }

        if ($val->getMinimum() !== null) {
            $json["minimum"] = $val->getMinimum();
        }

        if ($val->getMaximum() !== null) {
            $json["maximum"] = $val->getMaximum();
        }

        return $json;
    }

    private function generateStringValue(StringValue $val): array
    {
        $json = ["type" => "string"];

        if ($val->getDescription() !== null) {
            $json["description"] = $val->getDescription();
        }

        if ($val->getDefault() !== null) {
            $json["default"] = $val->getDefault();
        }

        if (count($val->getExamples()) > 0) {
            $json["examples"] = [];
            foreach ($val->getExamples() as $example) {
                $json["examples"][] = $example;
            }
        }

        if ($val->getPattern() !== null) {
            $json["pattern"] = $val->getPattern();
        }

        return $json;
    }

    private function generateObjectValue(ObjectValue $val): array
    {
        $json = ["type" => "object"];

        if ($val->getDescription() !== null) {
            $json["description"] = $val->getDescription();
        }

        // todo default
        // todo examples

        if (count($val->getProperties()) > 0) {
            $json["properties"] = [];
            $json["required"]   = [];

            foreach ($val->getProperties() as $property) {
                $json["properties"][$property->getName()] = $this->generateValue($property->getValue());
                if ($property->isRequired()) {
                    $json["required"][] = $property->getName();
                }
            }
        }

        return $json;
    }
}
