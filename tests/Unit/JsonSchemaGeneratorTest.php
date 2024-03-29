<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use MartinHeralecky\Jsonschema\JsonSchemaGenerator;
use MartinHeralecky\Jsonschema\Schema\BooleanSchema;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchemaProperty;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Value;
use PHPUnit\Framework\TestCase;

class JsonSchemaGeneratorTest extends TestCase
{
    private JsonSchemaGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new JsonSchemaGenerator();
    }

    public function testIntegerSchema(): void
    {
        $schema = new IntegerSchema("My Title", "My description.", new Value(3), [1, 2], [], null, null, -5, 5);
        $json = $this->gen->generate($schema);

        $this->assertSame("integer", $json["type"]);
        $this->assertSame("My Title", $json["title"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame(3, $json["default"]);
        $this->assertSame(1, $json["examples"][0]);
        $this->assertSame(2, $json["examples"][1]);
        $this->assertSame(-5, $json["minimum"]);
        $this->assertSame(5, $json["maximum"]);
        $this->assertArrayNotHasKey("enum", $json);
    }

    public function testStringSchema(): void
    {
        $schema = new StringSchema("My Title", "My description.", new Value("foo"), ["bar", "gee"]);
        $json = $this->gen->generate($schema);

        $this->assertSame("string", $json["type"]);
        $this->assertSame("My Title", $json["title"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame("foo", $json["default"]);
        $this->assertSame("bar", $json["examples"][0]);
        $this->assertSame("gee", $json["examples"][1]);
        $this->assertArrayNotHasKey("enum", $json);
        $this->assertArrayNotHasKey("pattern", $json);
    }

    public function testObjectSchema(): void
    {
        $schema = new ObjectSchema("My Title", "My description.", null, [
            new ObjectSchemaProperty("alfa", "alfa", new IntegerSchema(null, null, new Value(10))),
            new ObjectSchemaProperty("bravo", "bravo", new ObjectSchema("Another Title", "Another description.", null, [
                new ObjectSchemaProperty("charlie", "charlie", new StringSchema(null, null, new Value("def"))),
            ])),
        ]);
        $json = $this->gen->generate($schema);

        $this->assertSame("object", $json["type"]);
        $this->assertSame("My Title", $json["title"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame("integer", $json["properties"]["alfa"]["type"]);
        $this->assertSame("object", $json["properties"]["bravo"]["type"]);
        $this->assertSame("Another Title", $json["properties"]["bravo"]["title"]);
        $this->assertSame("Another description.", $json["properties"]["bravo"]["description"]);
        $this->assertSame("string", $json["properties"]["bravo"]["properties"]["charlie"]["type"]);
        $this->assertSame(["bravo"], $json["required"]);
    }

    public function testBooleanSchema(): void
    {
        $schema = new BooleanSchema("My Title", "My description.", new Value(true), [false, true]);
        $json = $this->gen->generate($schema);

        $this->assertSame("boolean", $json["type"]);
        $this->assertSame("My Title", $json["title"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame(true, $json["default"]);
        $this->assertSame(false, $json["examples"][0]);
        $this->assertSame(true, $json["examples"][1]);
        $this->assertArrayNotHasKey("enum", $json);
    }

    public function testNullSchema(): void
    {
        $schema = new NullSchema("My Title", "My description.", new Value(null), [null]);
        $json = $this->gen->generate($schema);

        $this->assertSame("null", $json["type"]);
        $this->assertSame("My Title", $json["title"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame(null, $json["default"]);
        $this->assertSame(null, $json["examples"][0]);
        $this->assertArrayNotHasKey("enum", $json);
    }
}
