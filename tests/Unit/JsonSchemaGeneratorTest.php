<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use MartinHeralecky\Jsonschema\JsonSchemaGenerator;
use MartinHeralecky\Jsonschema\Schema\IntegerValue;
use MartinHeralecky\Jsonschema\Schema\Schema;
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
        $var  = new IntegerValue("My description.", 3, [1, 2], -5, 5);
        $json = $this->gen->generate(new Schema(null, $var));

        $this->assertSame("integer", $json["type"]);
        $this->assertSame("My description.", $json["description"]);
        $this->assertSame(3, $json["default"]);
        $this->assertSame(1, $json["examples"][0]);
        $this->assertSame(2, $json["examples"][1]);
        $this->assertSame(-5, $json["minimum"]);
        $this->assertSame(5, $json["maximum"]);
    }
}
