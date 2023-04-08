<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Unit;

use DateTime;
use MartinHeralecky\Jsonschema\Attribute\DefaultValue;
use MartinHeralecky\Jsonschema\Attribute\Enum;
use MartinHeralecky\Jsonschema\Attribute\Example;
use MartinHeralecky\Jsonschema\Attribute\Max;
use MartinHeralecky\Jsonschema\Attribute\Min;
use MartinHeralecky\Jsonschema\Attribute\MinItems;
use MartinHeralecky\Jsonschema\Attribute\MinLength;
use MartinHeralecky\Jsonschema\Attribute\Name;
use MartinHeralecky\Jsonschema\Attribute\Type;
use MartinHeralecky\Jsonschema\Cast\DateTimeCast;
use MartinHeralecky\Jsonschema\Introspector\ClassIntrospector;
use MartinHeralecky\Jsonschema\Schema\BooleanSchema;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Schema\UnionSchema;
use MartinHeralecky\Jsonschema\TypeParser\TypeParser;
use PHPUnit\Framework\TestCase;

class ClassIntrospectorTest extends TestCase
{
    private ClassIntrospector $introspector;

    protected function setUp(): void
    {
        $this->introspector = new ClassIntrospector(new TypeParser());
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

                #[DefaultValue(10)]
                public int $foxtrot;

                #[DefaultValue("golf default")]
                public int $golf = 10;

                #[DefaultValue(null)]
                public int $hotel;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $this->assertSame(null, $schema->getProperties()[0]->getSchema()->getDefault());
        $this->assertSame(null, $schema->getProperties()[1]->getSchema()->getDefault());
        $this->assertSame(10, $schema->getProperties()[2]->getSchema()->getDefault()->getValue());
        $this->assertSame(10, $schema->getProperties()[3]->getSchema()->getDefault()->getValue());
        $this->assertSame(null, $schema->getProperties()[4]->getSchema()->getDefault()->getValue());
        $this->assertSame(10, $schema->getProperties()[5]->getSchema()->getDefault()->getValue());
        $this->assertSame("golf default", $schema->getProperties()[6]->getSchema()->getDefault()->getValue());
        $this->assertSame(null, $schema->getProperties()[7]->getSchema()->getDefault()->getValue());
    }

    public function testPropertyExamples(): void
    {
        $class =
            new class {
                public int $alfa;

                #[Example(10)]
                #[Example(20)]
                public int $bravo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $propAlfa = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $propAlfa);
        $this->assertCount(0, $propAlfa->getExamples());

        $propBravo = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $propBravo);
        $this->assertCount(2, $propBravo->getExamples());
        $this->assertSame(10, $propBravo->getExamples()[0]);
        $this->assertSame(20, $propBravo->getExamples()[1]);
    }

    public function testPropertyEnumValues(): void
    {
        $class =
            new class {
                public int $alfa;

                #[Enum([10, 20])]
                public int $bravo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $propAlfa = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $propAlfa);
        $this->assertCount(0, $propAlfa->getEnumValues());

        $propBravo = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $propBravo);
        $this->assertCount(2, $propBravo->getEnumValues());
        $this->assertSame(10, $propBravo->getEnumValues()[0]);
        $this->assertSame(20, $propBravo->getEnumValues()[1]);
    }

    public function testPropertyMinAndMax(): void
    {
        $class =
            new class {
                public int $alfa;

                #[Min(10)]
                #[Max(20)]
                public int $bravo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $propAlfa = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $propAlfa);
        $this->assertSame(null, $propAlfa->getMinimum());
        $this->assertSame(null, $propAlfa->getMaximum());

        $propBravo = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(IntegerSchema::class, $propBravo);
        $this->assertSame(10, $propBravo->getMinimum());
        $this->assertSame(20, $propBravo->getMaximum());
    }

    public function testPropertyMinLength(): void
    {
        $class =
            new class {
                public string $alfa;

                #[MinLength(10)]
                public string $bravo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $propAlfa = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(StringSchema::class, $propAlfa);
        $this->assertSame(0, $propAlfa->getMinLength());

        $propBravo = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(StringSchema::class, $propBravo);
        $this->assertSame(10, $propBravo->getMinLength());
    }

    public function testPropertyMinItems(): void
    {
        $class =
            new class {
                public array $alfa;

                #[MinItems(10)]
                public array $bravo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $propAlfa = $schema->getProperties()[0]->getSchema();
        $this->assertInstanceOf(ArraySchema::class, $propAlfa);
        $this->assertSame(0, $propAlfa->getMinItems());

        $propBravo = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(ArraySchema::class, $propBravo);
        $this->assertSame(10, $propBravo->getMinItems());
    }

    public function testPropertyCast(): void
    {
        $class =
            new class {
                public string $alfa;

                #[Type("string")]
                #[DateTimeCast]
                public DateTime $bravo;
            };

        $schema = $this->introspector->introspect($class::class);
        $this->assertInstanceOf(ObjectSchema::class, $schema);

        $propAlfa = $schema->getProperties()[0]->getSchema();
        $this->assertSame(null, $propAlfa->getJsonToPhpCast());
        $this->assertSame(null, $propAlfa->getPhpToJsonCast());

        $propBravo = $schema->getProperties()[1]->getSchema();
        $this->assertInstanceOf(DateTimeCast::class, $propBravo->getJsonToPhpCast());
        $this->assertInstanceOf(DateTimeCast::class, $propBravo->getPhpToJsonCast());
    }
}
