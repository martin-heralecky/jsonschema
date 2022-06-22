<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use MartinHeralecky\Jsonschema\Instantiator;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchemaProperty;
use PHPUnit\Framework\TestCase;

class InstantiatorTest extends TestCase
{
    private Instantiator $instantiator;

    protected function setUp(): void
    {
        $this->instantiator = new Instantiator();
    }

    public function test(): void
    {
        $class =
            new class {
                public int $alfa;
            };

        $schema = new ObjectSchema(null, null, [
            new ObjectSchemaProperty("alfa", new IntegerSchema()),
        ]);

        $instance = $this->instantiator->instantiate($schema, $class::class, [
            "alfa" => 123,
        ]);

        $this->assertInstanceOf($class::class, $instance);
        $this->assertSame(123, $instance->alfa);
    }
}
