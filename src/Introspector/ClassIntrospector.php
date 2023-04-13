<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Introspector;

use MartinHeralecky\Jsonschema\Attribute;
use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Schema\ArraySchema;
use MartinHeralecky\Jsonschema\Schema\BooleanSchema;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\MixedSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchemaProperty;
use MartinHeralecky\Jsonschema\Schema\Schema;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Schema\UnionSchema;
use MartinHeralecky\Jsonschema\Type\AtomicType;
use MartinHeralecky\Jsonschema\Type\Type;
use MartinHeralecky\Jsonschema\Type\UnionType;
use MartinHeralecky\Jsonschema\Value;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser as PhpStanTypeParser;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

/**
 * @todo Better name.
 */
class ClassIntrospector
{
    public function __construct(
        private readonly TypeParser $typeParser,
    ) {
    }

    // PREDCHOZI POKUSY JSOU V GITU

    /**
     * @param class-string $class
     * @throws IntrospectorException
     */
    public function introspect0(string $class): Schema
    {
        try {
            $rc = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new IntrospectorException("Class does not exist: $class", previous: $e);
        }

        $title = $this->getAttribute($rc, Attribute\Title::class)?->getValue();
        $description = $this->getClassDescription($rc);

        $properties = [];
        foreach ($rc->getProperties() as $prop) {
            $propSchema = $this->introspectProperty($prop);
            $properties[] = new ObjectSchemaProperty($this->getPropertyName($prop), $prop->getName(), $propSchema);
        }

        return new ObjectSchema($title, $description, $class, $properties);
    }

    private function introspectProperty0(ReflectionProperty $prop): Schema
    {
        $attrs = $prop->getAttributes();

        $typeAttr = self::pop($attrs, Attribute\Type::class);
        if ($typeAttr !== null) {
            $type = new AtomicType($typeAttr->getValue());
        } else {
            $type = $this->typeParser->parseProperty($prop);
        }

        $description = $this->getPropertyDescription($prop);

        $defaultAttr = self::pop($attrs, Attribute\DefaultValue::class);
        if ($defaultAttr !== null) {
            $default = new Value($defaultAttr->getValue());
        } else if ($prop->hasDefaultValue()) {
            $default = new Value($prop->getDefaultValue());
        } else {
            $default = null;
        }

        // todo mozna nepassovat dolu raw Attributes, ale naparsovat a passovat hodnoty (napr. zeshora ItemMaxLength
        //      kdyz se passne, tak dole uz nedava smysl - tam by melo byt ~Item~MaxLength).

        return $this->createSchema($type, $description, $default, $attrs);
    }

    /**
     * @param ReflectionAttribute[] $attrs
     */
    private function createSchema0(Type $type, ?string $desc, ?Value $default, array $attrs): Schema
    {
        $title = self::pop($attrs, Attribute\Title::class)?->getValue();
        $exampleAttrs = self::popAll($attrs, Attribute\Example::class);
        $examples = array_map(fn(Attribute\Example $exampleAttr) => $exampleAttr->getValue(), $exampleAttrs);
        $enum = self::pop($attrs, Attribute\Enum::class)?->getValues() ?? [];
        $jsonToPhpCast = self::pop($attrs, JsonToPhpCast::class);
        $phpToJsonCast = self::pop($attrs, PhpToJsonCast::class);

        if ($type instanceof AtomicType) {
            if ($type->getName() === "int") {
                return new IntegerSchema(
                    $title,
                    $desc,
                    $default,
                    $examples,
                    $enum,
                    $jsonToPhpCast,
                    $phpToJsonCast,
                    self::pop($attrs, Attribute\Min::class)?->getValue(),
                    self::pop($attrs, Attribute\Max::class)?->getValue(),
                );
            }

            if ($type->getName() === "string") {
                return new StringSchema(
                    $title,
                    $desc,
                    $default,
                    $examples,
                    $enum,
                    $jsonToPhpCast,
                    $phpToJsonCast,
                    self::pop($attrs, Attribute\MinLength::class)?->getValue() ?? 0,
                    null,
                );
            }

            if ($type->getName() === "bool") {
                return new BooleanSchema($title, $desc, $default, $examples, $enum, $jsonToPhpCast, $phpToJsonCast);
            }

            if ($type->getName() === "null") {
                return new NullSchema($title, $desc, $default, $examples, $enum, $jsonToPhpCast, $phpToJsonCast);
            }

            if ($type->getName() === "mixed") {
                return new MixedSchema($title, $desc, $default, $examples, $enum, $jsonToPhpCast, $phpToJsonCast);
            }

            if ($type->getName() === "array") {
                return new ArraySchema(
                    $this->createSchema($type->getGenericTypes()[0] ?? new AtomicType("mixed"), null, null, []),
                    $title,
                    $desc,
                    $default,
                    $examples,
                    $enum,
                    $jsonToPhpCast,
                    $phpToJsonCast,
                    self::pop($attrs, Attribute\MinItems::class)?->getValue() ?? 0,
                );
            }

            return $this->introspect($type->getName());
        }

        if ($type instanceof UnionType) {
            return new UnionSchema(
                array_map(fn(Type $t) => $this->createSchema($t, null, null, $attrs), $type->getTypes()),
                $title,
                $desc,
                $default,
                $examples,
                $enum,
                $jsonToPhpCast,
                $phpToJsonCast,
            );
        }

        throw new RuntimeException("Unknown type " . $type::class . ".");
    }

    /**
     * @param class-string $class
     * @throws IntrospectorException
     */
    public function introspect1(string $class): Schema
    {
        try {
            $rc = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new IntrospectorException("Class does not exist: $class", previous: $e);
        }

        $title = $this->getAttribute($rc, Attribute\Title::class)?->getValue();
        $description = $this->getClassDescription($rc);

        $properties = [];
        foreach ($rc->getProperties() as $prop) {
            $propSchema = $this->introspectProperty($prop);
            $properties[] = new ObjectSchemaProperty($this->getPropertyName($prop), $prop->getName(), $propSchema);
        }

        return new ObjectSchema($title, $description, $class, $properties);
    }

    private function introspectProperty1(ReflectionProperty $prop): Schema
    {
        $attrs = $prop->getAttributes();

        $typeAttr = self::pop($attrs, Attribute\Type::class);
        if ($typeAttr !== null) {
            $type = new AtomicType($typeAttr->getValue());
        } else {
            $type = $this->typeParser->parseProperty($prop);
        }

        $description = $this->getPropertyDescription($prop);

        $defaultAttr = self::pop($attrs, Attribute\DefaultValue::class);
        if ($defaultAttr !== null) {
            $default = new Value($defaultAttr->getValue());
        } else if ($prop->hasDefaultValue()) {
            $default = new Value($prop->getDefaultValue());
        } else {
            $default = null;
        }

        // todo mozna nepassovat dolu raw Attributes, ale naparsovat a passovat hodnoty (napr. zeshora ItemMaxLength
        //      kdyz se passne, tak dole uz nedava smysl - tam by melo byt ~Item~MaxLength).

        $schema = $this->createSchema($type);

        $title = self::pop($attrs, Attribute\Title::class)?->getValue();
        $exampleAttrs = self::popAll($attrs, Attribute\Example::class);
        $examples = array_map(fn(Attribute\Example $exampleAttr) => $exampleAttr->getValue(), $exampleAttrs);
        $enum = self::pop($attrs, Attribute\Enum::class)?->getValues() ?? [];
        $jsonToPhpCast = self::pop($attrs, JsonToPhpCast::class);
        $phpToJsonCast = self::pop($attrs, PhpToJsonCast::class);

        $schema->setTitle($title); // todo concat from original or replace if here (top) not null/empty
        $schema->setDescription($description); // todo concat from original or replace if here (top) not null/empty
        $schema->setDefault($default); // todo concat from original or replace if here (top) not null/empty
        $schema->setExamples($examples); // todo concat from original or replace if here (top) not null/empty
        $schema->setEnumValues($enum); // todo concat from original or replace if here (top) not null/empty
        $schema->setJsonToPhpCast($jsonToPhpCast); // todo concat from original or replace if here (top) not null/empty
        $schema->setPhpToJsonCast($phpToJsonCast); // todo concat from original or replace if here (top) not null/empty

        // override values. property attribute has higher priority than class attribute.

        $minAttr = self::pop($attrs, Attribute\Min::class);
        if ($minAttr !== null) {
            if ($schema instanceof IntegerSchema) {
                $schema->setMinimum($minAttr->getValue());
            }
        }

        $maxAttr = self::pop($attrs, Attribute\Max::class);
        if ($maxAttr !== null) {
            if ($schema instanceof IntegerSchema) {
                $schema->setMaximum($maxAttr->getValue());
            }
        }

        $minItemsAttr = self::pop($attrs, Attribute\MinItems::class);
        if ($minItemsAttr !== null) {
            if ($schema instanceof ArraySchema) {
                $schema->setMinItems($minItemsAttr->getValue());
            }
        }

        $minLengthAttr = self::pop($attrs, Attribute\MinLength::class);
        if ($minLengthAttr !== null) {
            if ($schema instanceof StringSchema) {
                $schema->setMinLength($minLengthAttr->getValue());
            }
        }

        return $schema;
    }

    private function createSchema1(Type $type): Schema
    {
        if ($type instanceof AtomicType) {
            if ($type->getName() === "int") {
                return new IntegerSchema();
            }

            if ($type->getName() === "string") {
                return new StringSchema();
            }

            if ($type->getName() === "bool") {
                return new BooleanSchema();
            }

            if ($type->getName() === "null") {
                return new NullSchema();
            }

            if ($type->getName() === "mixed") {
                return new MixedSchema();
            }

            if ($type->getName() === "array") {
                // todo add/concat ItemSchema etc. to item schemas
                // todo SPIS NE - bude nahore v introspectProperty - tady vubec nemam atributy
                return new ArraySchema($this->createSchema($type->getGenericTypes()[0]));
            }

            return $this->introspect($type->getName());
        }

        if ($type instanceof UnionType) {
            // todo apply Min, Max etc. to appropriate child schemas (Min, Max to IntegerSchema, MinLength to StringSchema...)
            // todo SPIS NE - bude nahore v introspectProperty - tady vubec nemam atributy
            return new UnionSchema(array_map($this->createSchema(...), $type->getTypes()));
        }

        throw new RuntimeException("Unknown type " . $type::class . ".");
    }




    /**
     * @param class-string $class
     * @throws IntrospectorException
     */
    public function introspect(string $class): Schema
    {
        return $this->introspectType(new AtomicType($class));
    }

    public function introspectType(Type $type): Schema
    {
        if ($type instanceof AtomicType) {
            if ($type->getName() === "int") {
                return new IntegerSchema();
            }

            if ($type->getName() === "string") {
                return new StringSchema();
            }

            if ($type->getName() === "bool") {
                return new BooleanSchema();
            }

            if ($type->getName() === "null") {
                return new NullSchema();
            }

            if ($type->getName() === "mixed") {
                return new MixedSchema();
            }

            if ($type->getName() === "array") {
                return new ArraySchema($this->introspectType($type->getGenericTypes()[0]));
            }

            try {
                $rc = new ReflectionClass($type->getName());
            } catch (ReflectionException $e) {
                throw new IntrospectorException("Class does not exist: {$type->getName()}", previous: $e);
            }

            $title = $this->getAttribute($rc, Attribute\Title::class)?->getValue();
            $description = $this->getClassDescription($rc);

            if ($rc->isInterface()) {
                $subSchemas = [];
                foreach (get_declared_classes() as $class) {
                    if (in_array($rc->getName(), class_implements($class), true)) {
                        $subSchemas[] = $this->introspectType(new AtomicType($class));
                    }
                }

                // todo default, examples, enums...
                return new UnionSchema($subSchemas, $title, $description);
            }

            $properties = [];
            foreach ($rc->getProperties() as $prop) {
                $properties[] = $this->introspectProperty($prop);
            }

            // todo ne vzdy ObjectSchema - muze byt #[Min(10)] class Neco implements InlineInteger {}
            // todo default, examples, enums...
            return new ObjectSchema($title, $description, $type->getName(), $properties);
        }

        if ($type instanceof UnionType) {
            return new UnionSchema(array_map($this->introspectType(...), $type->getTypes()));
        }

        throw new RuntimeException("Unknown type " . $type::class . ".");
    }

    private function introspectProperty(ReflectionProperty $prop): ObjectSchemaProperty
    {
        $typeAttr = $this->getAttribute($prop, Attribute\Type::class);
        if ($typeAttr !== null) {
            $type = new AtomicType($typeAttr->getValue());
        } else {
            $type = $this->typeParser->parseProperty($prop);
        }

        $defaultAttr = $this->getAttribute($prop, Attribute\DefaultValue::class);
        if ($defaultAttr !== null) {
            $default = new Value($defaultAttr->getValue());
        } else if ($prop->hasDefaultValue()) {
            $default = new Value($prop->getDefaultValue());
        } else {
            $default = null;
        }

        $exampleAttrs = $this->getAttributes($prop, Attribute\Example::class);
        $examples = array_map(fn(Attribute\Example $exampleAttr) => $exampleAttr->getValue(), $exampleAttrs);

        $enum = $this->getAttribute($prop, Attribute\Enum::class)?->getValues() ?? [];

        $jsonToPhpCast = $this->getAttribute($prop, JsonToPhpCast::class);
        $phpToJsonCast = $this->getAttribute($prop, PhpToJsonCast::class);

        return new ObjectSchemaProperty(
            $this->getAttribute($prop, Attribute\Name::class)?->getValue() ?? $prop->getName(),
            $prop->getName(),
            $this->introspectType($type),
            $this->getPropertyDescription($prop),
            $default,
            $examples,
            $enum,
        );
    }

    private function getClassDescription(ReflectionClass $class): ?string
    {
        if ($class->getDocComment() === false) {
            return null;
        }

        $node = $this->parsePhpDocNode($class->getDocComment());

        $nodes = array_filter($node->children, fn(PhpDocChildNode $n) => $n instanceof PhpDocTextNode);
        $texts = array_map(fn(PhpDocTextNode $n) => $n->text, $nodes);
        $desc = join(" ", $texts);

        $desc = preg_replace("/\s+/", " ", $desc);
        $desc = trim($desc);

        if ($desc === "") {
            return null;
        }

        return $desc;
    }

    /**
     * @throws IntrospectorException
     */
    private function getPropertyDescription(ReflectionProperty $prop): ?string
    {
        if ($prop->getDocComment() === false) {
            return null;
        }

        $node = $this->parsePhpDocNode($prop->getDocComment());

        $tags = $node->getVarTagValues();

        if (count($tags) === 0) {
            return null;
        }

        if (count($tags) > 1) {
            throw new IntrospectorException("Unable to process multiple @var annotations.");
        }

        $desc = $tags[0]->description;
        $desc = preg_replace("/\s+/", " ", $desc);
        $desc = trim($desc);

        if ($desc === "") {
            return null;
        }

        return $desc;
    }

    /**
     * @template T
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    private function getAttribute(ReflectionProperty|ReflectionClass $obj, string $attributeClass): ?object
    {
        return $this->getAttributes($obj, $attributeClass)[0] ?? null;
    }

    /**
     * @template T
     * @param class-string<T> $attributeClass
     * @return T[]
     */
    private function getAttributes(ReflectionProperty|ReflectionClass $obj, string $attributeClass): array
    {
        $attrs = [];

        $refAttrs = $obj->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);
        foreach ($refAttrs as $refAttr) {
            $attrs[] = $refAttr->newInstance();
        }

        return $attrs;
    }

    private function parsePhpDocNode(string $phpDoc): PhpDocNode
    {
        $lexer = new Lexer();

        $tokens = $lexer->tokenize($phpDoc);
        $tokensIt = new TokenIterator($tokens);

        $constExprParser = new ConstExprParser();
        $parser = new PhpDocParser(new PhpStanTypeParser($constExprParser), $constExprParser);

        return $parser->parse($tokensIt);
    }
}
