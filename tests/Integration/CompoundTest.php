<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Integration;

use MartinHeralecky\Jsonschema\Instantiator;
use MartinHeralecky\Jsonschema\Introspector;
use MartinHeralecky\Jsonschema\JsonSchemaGenerator;
use PHPUnit\Framework\TestCase;

class CompoundTest extends TestCase
{
    public function testInstantiate(): void
    {
        $class =
            new class {
                public int $alfa;
            };

        $json = [
            "alfa" => 123,
        ];

        $introspector = new Introspector();
        $instantiator = new Instantiator();

        $schema   = $introspector->introspect($class::class);
        $instance = $instantiator->instantiate($schema, $class::class, $json);

        $this->assertInstanceOf($class::class, $instance);
        $this->assertSame(123, $instance->alfa);
    }

    public function testGenerateJsonSchema(): void
    {
        $class =
            new class {
                /**
                 * @var int A description.
                 */
                public int $alfa;
            };

        $introspector = new Introspector();
        $generator    = new JsonSchemaGenerator();

        $schema = $introspector->introspect($class::class);
        $json   = $generator->generate($schema);

        $this->assertSame("object", $json["type"]);
        $this->assertCount(1, $json["properties"]);
        $this->assertSame("integer", $json["properties"]["alfa"]["type"]);
        $this->assertSame("A description.", $json["properties"]["alfa"]["description"]);
    }
}
