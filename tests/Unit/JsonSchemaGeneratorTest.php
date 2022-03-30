<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use MartinHeralecky\Jsonschema\JsonSchemaGenerator;
use MartinHeralecky\Jsonschema\Schema\IntegerValue;
use MartinHeralecky\Jsonschema\Schema\ObjectValue;
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

    public function testInteger()
    {
        $val  = new IntegerValue("My description.", 3, [1, 2], -5, 5);
        $json = $this->gen->generate(new Schema(null, $val));

        $this->assertSame("integer", $json["type"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame(3, $json["default"]);
        $this->assertSame(1, $json["examples"][0]);
        $this->assertSame(2, $json["examples"][1]);
        $this->assertSame(-5, $json["minimum"]);
        $this->assertSame(5, $json["maximum"]);
    }

    public function testString()
    {
        $val  = new StringValue("My description.", "foo", ["bar", "gee"], null);
        $json = $this->gen->generate(new Schema(null, $val));

        $this->assertSame("string", $json["type"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame("foo", $json["default"]);
        $this->assertSame("bar", $json["examples"][0]);
        $this->assertSame("gee", $json["examples"][1]);
        $this->assertArrayNotHasKey("pattern", $json);
    }

    public function testObject()
    {
        $val  = new ObjectValue("My description.", []);
        $json = $this->gen->generate(new Schema(null, $val));

        $this->assertSame("object", $json["type"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertArrayNotHasKey("properties", $json);
        $this->assertArrayNotHasKey("required", $json);
    }
}
