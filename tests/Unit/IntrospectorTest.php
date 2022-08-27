<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use DateTime;
use MartinHeralecky\Jsonschema\Attribute\Enum;
use MartinHeralecky\Jsonschema\Attribute\Example;
use MartinHeralecky\Jsonschema\Attribute\Max;
use MartinHeralecky\Jsonschema\Attribute\Min;
use MartinHeralecky\Jsonschema\Attribute\Name;
use MartinHeralecky\Jsonschema\Attribute\Type;
use MartinHeralecky\Jsonschema\Cast\DateTimeCast;
use MartinHeralecky\Jsonschema\Introspector;
use MartinHeralecky\Jsonschema\Schema\BooleanSchema;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Schema\UnionSchema;
use MartinHeralecky\Jsonschema\TypeParser\TypeParser;
use PHPUnit\Framework\TestCase;

class IntrospectorTest extends TestCase
{
    private Introspector $introspector;

    protected function setUp(): void
    {
        $this->introspector = new Introspector(new TypeParser());
    }

    public function testPropertyName(): void
    {
        $class =
            new class {
                public int $alfa;

                #[Name("notBravo")]
                public int $bravo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $this->assertSame("alfa", $schema->getProperties()[0]->getPhpName());
        $this->assertSame("alfa", $schema->getProperties()[0]->getName());
        $this->assertSame("bravo", $schema->getProperties()[1]->getPhpName());
        $this->assertSame("notBravo", $schema->getProperties()[1]->getName());
    }

    public function testPropertyType(): void
    {
        $class =
            new class {
                public int $alfa;
                public ?int $bravo;
                public int|string $charlie;
                public int|null|string|bool $delta;

                #[Type("string")]
                public int $echo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $this->assertInstanceOf(IntegerSchema::class, $schema->getProperties()[0]->getSchema());

        $prop = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(UnionSchema::class, $prop);
        $this->assertInstanceOf(IntegerSchema::class, $prop->getSchemas()[0]);
        $this->assertInstanceOf(NullSchema::class, $prop->getSchemas()[1]);

        $prop = $schema->getProperties()[2]->getSchema();
        $this->assertInstanceOf(UnionSchema::class, $prop);
        $this->assertInstanceOf(StringSchema::class, $prop->getSchemas()[0]);
        $this->assertInstanceOf(IntegerSchema::class, $prop->getSchemas()[1]);

        $prop = $schema->getProperties()[3]->getSchema();
        $this->assertInstanceOf(UnionSchema::class, $prop);
        $this->assertInstanceOf(StringSchema::class, $prop->getSchemas()[0]);
        $this->assertInstanceOf(IntegerSchema::class, $prop->getSchemas()[1]);
        $this->assertInstanceOf(BooleanSchema::class, $prop->getSchemas()[2]);
        $this->assertInstanceOf(NullSchema::class, $prop->getSchemas()[3]);

        $prop = $schema->getProperties()[4]->getSchema();
        $this->assertInstanceOf(StringSchema::class, $prop);
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

                public int $echo;
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
                public int $alfa;
                public ?int $bravo;
                public int $charlie = 10;
                public ?int $delta = 10;
                public ?int $echo = null;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $this->assertSame(null, $schema->getProperties()[0]->getSchema()->getDefault());
        $this->assertSame(null, $schema->getProperties()[1]->getSchema()->getDefault());
        $this->assertSame(10, $schema->getProperties()[2]->getSchema()->getDefault()->getValue());
        $this->assertSame(10, $schema->getProperties()[3]->getSchema()->getDefault()->getValue());
        $this->assertSame(null, $schema->getProperties()[4]->getSchema()->getDefault()->getValue());
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

    public function testPropertyCast(): void
    {
        $class =
            new class {
                #[Type("string")]
                #[DateTimeCast]
                public DateTime $alfa;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $prop = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(DateTimeCast::class, $prop->getJsonToPhpCast());
        $this->assertInstanceOf(DateTimeCast::class, $prop->getPhpToJsonCast());
    }
}
