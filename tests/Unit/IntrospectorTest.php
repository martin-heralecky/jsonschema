<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use MartinHeralecky\Jsonschema\Attribute\Enum;
use MartinHeralecky\Jsonschema\Attribute\Example;
use MartinHeralecky\Jsonschema\Attribute\Max;
use MartinHeralecky\Jsonschema\Attribute\Min;
use MartinHeralecky\Jsonschema\Introspector;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Schema\UnionSchema;
use PHPUnit\Framework\TestCase;

class IntrospectorTest extends TestCase
{
    private Introspector $introspector;

    protected function setUp(): void
    {
        $this->introspector = new Introspector();
    }

    public function testPropertyType(): void
    {
        $class =
            new class {
                public int             $alfa;
                public ?int            $bravo;
                public int|string      $charlie;
                public int|null|string $delta;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $this->assertSame("alfa", $schema->getProperties()[0]->getName());
        $this->assertInstanceOf(IntegerSchema::class, $schema->getProperties()[0]->getSchema());

        $this->assertSame("bravo", $schema->getProperties()[1]->getName());
        $prop = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(UnionSchema::class, $prop);
        $this->assertInstanceOf(IntegerSchema::class, $prop->getSchemas()[0]);
        $this->assertInstanceOf(NullSchema::class, $prop->getSchemas()[1]);

        $this->assertSame("charlie", $schema->getProperties()[2]->getName());
        $prop = $schema->getProperties()[2]->getSchema();
        $this->assertInstanceOf(UnionSchema::class, $prop);
        $this->assertInstanceOf(StringSchema::class, $prop->getSchemas()[0]);
        $this->assertInstanceOf(IntegerSchema::class, $prop->getSchemas()[1]);

        $this->assertSame("delta", $schema->getProperties()[3]->getName());
        $prop = $schema->getProperties()[3]->getSchema();
        $this->assertInstanceOf(UnionSchema::class, $prop);
        $this->assertInstanceOf(StringSchema::class, $prop->getSchemas()[0]);
        $this->assertInstanceOf(IntegerSchema::class, $prop->getSchemas()[1]);
        $this->assertInstanceOf(NullSchema::class, $prop->getSchemas()[2]);
    }

    public function testPropertyDescription(): void
    {
        $class =
            new class {
                /**
                 * @var int A standard description.
                 */
                public int $alfa;

                /** @var int A single line description. */
                public int $bravo;

                /**
                 * @var int A multi line description. A multi line
                 *          description. A multi line description.
                 */
                public int $charlie;

                /** @var int */
                public int $delta;

                public int $epsilon;
            };

        $schema = $this->introspector->introspect($class::class);

        $this->assertInstanceOf(ObjectSchema::class, $schema);
        $this->assertSame("A standard description.", $schema->getProperties()[0]->getSchema()->getDescription());
        $this->assertSame("A single line description.", $schema->getProperties()[1]->getSchema()->getDescription());
        $this->assertSame(
            "A multi line description. A multi line description. A multi line description.",
            $schema->getProperties()[2]->getSchema()->getDescription(),
        );
        $this->assertSame(null, $schema->getProperties()[3]->getSchema()->getDescription());
        $this->assertSame(null, $schema->getProperties()[4]->getSchema()->getDescription());
    }

    public function testClassDescription(): void
    {
        $class =
            /**
             * A standard description.
             */
            new class {
            };

        $schema = $this->introspector->introspect($class::class);

        $this->assertInstanceOf(ObjectSchema::class, $schema);
        $this->assertSame("A standard description.", $schema->getDescription());

        $class =
            /**
             * A multi line description. A multi line
             * description.
             *
             * A multi line description.
             */
            new class {
            };

        $schema = $this->introspector->introspect($class::class);

        $this->assertInstanceOf(ObjectSchema::class, $schema);
        $this->assertSame(
            "A multi line description. A multi line description. A multi line description.",
            $schema->getDescription(),
        );

        $class =
            new class {
            };

        $schema = $this->introspector->introspect($class::class);

        $this->assertInstanceOf(ObjectSchema::class, $schema);
        $this->assertSame(null, $schema->getDescription());
    }

    public function testPropertyDefault(): void
    {
        $class =
            new class {
                public int  $alfa;
                public ?int $bravo;
                public int  $charlie = 10;
                public ?int $delta   = 10;
                public ?int $echo    = null;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $this->assertNull($schema->getProperties()[0]->getSchema()->getDefault());
        $this->assertNull($schema->getProperties()[1]->getSchema()->getDefault());
        $this->assertSame(10, $schema->getProperties()[2]->getSchema()->getDefault()->getValue());
        $this->assertSame(10, $schema->getProperties()[3]->getSchema()->getDefault()->getValue());
        $this->assertNull($schema->getProperties()[4]->getSchema()->getDefault()->getValue());
    }

    public function testPropertyExamples(): void
    {
        $class =
            new class {
                #[Example(10)]
                #[Example(20)]
                public int $alfa;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $prop = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $prop);
        $this->assertCount(2, $prop->getExamples());
        $this->assertSame(10, $prop->getExamples()[0]);
        $this->assertSame(20, $prop->getExamples()[1]);
    }

    public function testPropertyEnumValues(): void
    {
        $class =
            new class {
                #[Enum([10, 20])]
                public int $alfa;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $prop = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $prop);
        $this->assertCount(2, $prop->getEnumValues());
        $this->assertSame(10, $prop->getEnumValues()[0]);
        $this->assertSame(20, $prop->getEnumValues()[1]);
    }

    public function testPropertyMinAndMax(): void
    {
        $class =
            new class {
                #[Min(10)]
                #[Max(20)]
                public int $alfa;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $prop = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $prop);
        $this->assertSame(10, $prop->getMinimum());
        $this->assertSame(20, $prop->getMaximum());
    }
}
