<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use MartinHeralecky\Jsonschema\JsonSchemaGenerator;
use MartinHeralecky\Jsonschema\Schema\IntegerValue;
use MartinHeralecky\Jsonschema\Schema\ObjectValue;
use MartinHeralecky\Jsonschema\Schema\ObjectValueProperty;
use MartinHeralecky\Jsonschema\Schema\Schema;
use MartinHeralecky\Jsonschema\Schema\StringValue;
use PHPUnit\Framework\TestCase;

class JsonSchemaGeneratorTest extends TestCase
{
    private JsonSchemaGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new JsonSchemaGenerator();
    }

    public function testSchema(): void
    {
        $json = $this->gen->generate(new Schema(new IntegerValue(), "My Title"));

        $this->assertSame("My Title", $json["title"]);
    }

    public function testIntegerValue(): void
    {
        $val  = new IntegerValue("My description.", 3, [1, 2], -5, 5);
        $json = $this->gen->generate(new Schema($val));

        $this->assertSame("integer", $json["type"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame(3, $json["default"]);
        $this->assertSame(1, $json["examples"][0]);
        $this->assertSame(2, $json["examples"][1]);
        $this->assertSame(-5, $json["minimum"]);
        $this->assertSame(5, $json["maximum"]);
    }

    public function testStringValue(): void
    {
        $val  = new StringValue("My description.", "foo", ["bar", "gee"], null);
        $json = $this->gen->generate(new Schema($val));

        $this->assertSame("string", $json["type"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame("foo", $json["default"]);
        $this->assertSame("bar", $json["examples"][0]);
        $this->assertSame("gee", $json["examples"][1]);
        $this->assertArrayNotHasKey("pattern", $json);
    }

    public function testObjectValue(): void
    {
        $val  = new ObjectValue("My description.", [
            new ObjectValueProperty("foo", true, new IntegerValue()),
            new ObjectValueProperty("bar", false, new ObjectValue("Another description.", [
                new ObjectValueProperty("gee", true, new StringValue()),
            ])),
        ]);
        $json = $this->gen->generate(new Schema($val));

        $this->assertSame("object", $json["type"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame("integer", $json["properties"]["foo"]["type"]);
        $this->assertSame("object", $json["properties"]["bar"]["type"]);
        $this->assertSame("Another description.", $json["properties"]["bar"]["description"]);
        $this->assertSame("string", $json["properties"]["bar"]["properties"]["gee"]["type"]);
        $this->assertSame(["gee"], $json["properties"]["bar"]["required"]);
        $this->assertSame(["foo"], $json["required"]);
    }
}
