<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use MartinHeralecky\Jsonschema\Instantiator\PublicPropertySetInstantiator;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchemaProperty;
use PHPUnit\Framework\TestCase;

class PublicPropertySetInstantiatorTest extends TestCase
{
    private PublicPropertySetInstantiator $instantiator;

    protected function setUp(): void
    {
        $this->instantiator = new PublicPropertySetInstantiator();
    }

    public function test(): void
    {
        $class =
            new class {
                public int $alfa;
            };

        $schema = new ObjectSchema(null, null, $class::class, [
            new ObjectSchemaProperty("alfa", "alfa", new IntegerSchema()),
        ]);

        $instance = $this->instantiator->instantiate($schema, [
            "alfa" => 123,
        ]);

        $this->assertInstanceOf($class::class, $instance);
        $this->assertSame(123, $instance->alfa);
    }
}
