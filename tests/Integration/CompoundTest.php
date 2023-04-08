<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Integration;

use MartinHeralecky\Jsonschema\Instantiator\PublicPropertySetInstantiator;
use MartinHeralecky\Jsonschema\Introspector\ClassIntrospector;
use MartinHeralecky\Jsonschema\Introspector\TypeParser;
use MartinHeralecky\Jsonschema\JsonSchemaGenerator;
use PHPUnit\Framework\TestCase;

class CompoundTest extends TestCase
{
    public function testIntrospectAndInstantiate(): void
    {
        $class =
            new class {
                public int $alfa;
            };

        $data = [
            "alfa" => 123,
        ];

        $introspector = new ClassIntrospector(new TypeParser());
        $instantiator = new PublicPropertySetInstantiator();

        $schema = $introspector->introspect($class::class);
        $instance = $instantiator->instantiate($schema, $data);

        $this->assertInstanceOf($class::class, $instance);
        $this->assertSame(123, $instance->alfa);
    }

    public function testIntrospectAndGenerateJsonSchema(): void
    {
        $class =
            new class {
                /**
                 * @var int A description.
                 */
                public int $alfa;
            };

        $introspector = new ClassIntrospector(new TypeParser());
        $generator = new JsonSchemaGenerator();

        $schema = $introspector->introspect($class::class);
        $jsonSchema = $generator->generate($schema);

        $this->assertSame("object", $jsonSchema["type"]);
        $this->assertCount(1, $jsonSchema["properties"]);
        $this->assertSame("integer", $jsonSchema["properties"]["alfa"]["type"]);
        $this->assertSame("A description.", $jsonSchema["properties"]["alfa"]["description"]);
    }
}
