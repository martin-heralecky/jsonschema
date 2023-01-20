<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Introspector;

use MartinHeralecky\Jsonschema\Attribute;
use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Exception\IntrospectionException;
use MartinHeralecky\Jsonschema\Exception\UnknownTypeException;
use MartinHeralecky\Jsonschema\Schema\BooleanSchema;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchemaProperty;
use MartinHeralecky\Jsonschema\Schema\Schema;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Schema\UnionSchema;
use MartinHeralecky\Jsonschema\TypeParser\Type\AtomicType;
use MartinHeralecky\Jsonschema\TypeParser\Type\Type;
use MartinHeralecky\Jsonschema\TypeParser\Type\UnionType;
use MartinHeralecky\Jsonschema\TypeParser\TypeParser;
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

    /**
     * @param class-string $class
     * @throws IntrospectionException
     */
    public function introspect(string $class): Schema
    {
        try {
            $rc = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new IntrospectionException("Class does not exist: $class", previous: $e);
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

    /**
     * @throws IntrospectionException
     */
    private function introspectProperty(ReflectionProperty $prop): Schema
    {
        $type = $this->getPropertyType($prop);

        $title = $this->getPropertyTitle($prop);
        $description = $this->getPropertyDescription($prop);
        $default = $this->getPropertyDefault($prop);
        $examples = $this->getPropertyExamples($prop);
        $enum = $this->getPropertyEnumValues($prop);

        $jsonToPhpCast = $this->getAttribute($prop, JsonToPhpCast::class);
        $phpToJsonCast = $this->getAttribute($prop, PhpToJsonCast::class);

        if ($type instanceof AtomicType) {
            return $this->introspectAtomicPropertyType(
                $type,
                $title,
                $description,
                $default,
                $examples,
                $enum,
                $jsonToPhpCast,
                $phpToJsonCast,
                $prop,
            );
        }

        if ($type instanceof UnionType) {
            $schemas = [];
            foreach ($type->getTypes() as $t) {
                $schemas[] = $this->introspectAtomicPropertyType($t, null, null, null, [], [], null, null, $prop);
            }

            return new UnionSchema(
                $schemas,
                $title,
                $description,
                $default,
                $examples,
                $enum,
                $jsonToPhpCast,
                $phpToJsonCast,
            );
        }

        throw new RuntimeException(sprintf(
            "Unknown type %s on %s::%s.",
            $type::class,
            $prop->getDeclaringClass()->getName(),
            $prop->getName(),
        ));
    }

    /**
     * @throws IntrospectionException
     */
    private function introspectAtomicPropertyType(
        AtomicType $type,
        ?string $title,
        ?string $description,
        ?Value $default,
        array $examples,
        array $enumValues,
        ?JsonToPhpCast $jsonToPhpCast,
        ?PhpToJsonCast $phpToJsonCast,
        ReflectionProperty $prop,
    ): Schema {
        if ($type->getName() === "int") {
            return new IntegerSchema(
                $title,
                $description,
                $default,
                $examples,
                $enumValues,
                $jsonToPhpCast,
                $phpToJsonCast,
                $this->getAttribute($prop, Attribute\Min::class)?->getValue(),
                $this->getAttribute($prop, Attribute\Max::class)?->getValue(),
            );
        }

        if ($type->getName() === "string") {
            return new StringSchema(
                $title,
                $description,
                $default,
                $examples,
                $enumValues,
                $jsonToPhpCast,
                $phpToJsonCast,
            );
        }

        if ($type->getName() === "bool") {
            return new BooleanSchema(
                $title,
                $description,
                $default,
                $examples,
                $enumValues,
                $jsonToPhpCast,
                $phpToJsonCast,
            );
        }

        if ($type->getName() === "null") {
            return new NullSchema(
                $title,
                $description,
                $default,
                $examples,
                $enumValues,
                $jsonToPhpCast,
                $phpToJsonCast,
            );
        }

        return $this->introspect($type->getName());
    }

    private function getPropertyName(ReflectionProperty $prop): string
    {
        return $this->getAttribute($prop, Attribute\Name::class)?->getValue() ?? $prop->getName();
    }

    /**
     * @throws UnknownTypeException
     */
    private function getPropertyType(ReflectionProperty $prop): Type
    {
        $typeAttr = $this->getAttribute($prop, Attribute\Type::class);
        if ($typeAttr !== null) {
            return new AtomicType($typeAttr->getValue());
        }

        return $this->typeParser->parseProperty($prop);
    }

    private function getPropertyTitle(ReflectionProperty $prop): ?string
    {
        return $this->getAttribute($prop, Attribute\Title::class)?->getValue();
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
     * @throws IntrospectionException
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
            throw new IntrospectionException("Unable to process multiple @var annotations.");
        }

        $desc = $tags[0]->description;
        $desc = preg_replace("/\s+/", " ", $desc);
        $desc = trim($desc);

        if ($desc === "") {
            return null;
        }

        return $desc;
    }

    private function getPropertyDefault(ReflectionProperty $prop): ?Value
    {
        $defaultAttr = $this->getAttribute($prop, Attribute\DefaultValue::class);
        if ($defaultAttr !== null) {
            return new Value($defaultAttr->getValue());
        }

        if ($prop->hasDefaultValue()) {
            return new Value($prop->getDefaultValue());
        }

        return null;
    }

    private function getPropertyExamples(ReflectionProperty $prop): array
    {
        return array_map(
            fn(Attribute\Example $attr) => $attr->getValue(),
            $this->getAttributes($prop, Attribute\Example::class),
        );
    }

    private function getPropertyEnumValues(ReflectionProperty $prop): array
    {
        return $this->getAttribute($prop, Attribute\Enum::class)?->getValues() ?? [];
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
