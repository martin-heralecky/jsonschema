<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema;

use InvalidArgumentException;
use MartinHeralecky\Jsonschema\Attribute;
use MartinHeralecky\Jsonschema\Exception\IntrospectionException;
use MartinHeralecky\Jsonschema\Schema\BooleanSchema;
use MartinHeralecky\Jsonschema\Schema\IntegerSchema;
use MartinHeralecky\Jsonschema\Schema\NullSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\ObjectSchemaProperty;
use MartinHeralecky\Jsonschema\Schema\Schema;
use MartinHeralecky\Jsonschema\Schema\StringSchema;
use MartinHeralecky\Jsonschema\Schema\UnionSchema;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;
use Traversable;

class Introspector
{
    private Lexer        $docLexer;
    private PhpDocParser $docParser;

    private TypeParser $typeParser;

    public function __construct()
    {
        // todo to DI

        $this->docLexer = new Lexer();

        $constExprParser = new ConstExprParser();
        $this->docParser = new PhpDocParser(new TypeParser($constExprParser), $constExprParser);

        $this->typeParser = new TypeParser();
    }

    /**
     * @param class-string $class
     */
    public function introspect(string $class): Schema
    {
        try {
            $rc = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException("Class does not exist: $class", previous: $e);
        }

        $title       = $this->getAttribute($rc, Attribute\Title::class)?->getTitle();
        $description = $this->getClassDescription($rc);

        $properties = [];
        foreach ($rc->getProperties() as $prop) {
            $propSchema   = $this->introspectProperty($prop);
            $properties[] = new ObjectSchemaProperty($this->getPropertyName($prop), $prop->getName(), $propSchema);
        }

        return new ObjectSchema($title, $description, $properties);
    }

    /**
     * @todo
     */
    private function introspectProperty(ReflectionProperty $prop): Schema
    {
        // todo najit jak phpstan merguje php typy a phpdoc typy. taky se na ne musi divat, kdyz treba phpdoc vubec
        //      neexistuje. mozna budu moct svuj TypeParser uplne zrusit.

        $types = $this->getPropertyType($prop);

        $description = $this->getPropertyDescription($prop);
        $default     = $this->getPropertyDefault($prop);
        $examples    = $this->getPropertyExamples($prop);
        $enum        = $this->getPropertyEnumValues($prop);

        if (count($types) === 1) {
            return $this->introspectSinglePropertyType($types[0], $description, $default, $examples, $enum, $prop);
        }

        $values = [];
        foreach ($types as $type) {
            $values[] = $this->introspectSinglePropertyType($type, null, null, [], [], $prop);
        }

        return new UnionSchema($values, null, $description, $default, $examples, $enum);
    }

    /**
     * @todo
     */
    private function introspectSinglePropertyType(
        string             $type,
        ?string            $description,
        ?Value             $default,
        array              $examples,
        array              $enumValues,
        ReflectionProperty $prop,
    ): Schema {
        if ($type === "int") {
            return new IntegerSchema(
                null,
                $description,
                $default,
                $examples,
                $enumValues,
                $this->getAttribute($prop, Attribute\Min::class)?->getValue(),
                $this->getAttribute($prop, Attribute\Max::class)?->getValue(),
            );
        }

        if ($type === "string") {
            return new StringSchema(null, $description, $default, $examples, $enumValues, null /* todo */);
        }

        if ($type === "bool") {
            return new BooleanSchema(null, $description, $default, $examples, $enumValues);
        }

        if ($type === "null") {
            return new NullSchema(null, $description, $default, $examples, $enumValues);
        }

        if (class_exists($type)) {
            return $this->introspect($type);
        }

//        throw new RuntimeException(sprintf(
//            "Unknown property type %s on %s::%s.",
//            $type,
//            $prop->getDeclaringClass()->getName(),
//            $prop->getName(),
//        ));
    }

    private function getPropertyName(ReflectionProperty $prop): string
    {
        return $this->getAttribute($prop, Attribute\Name::class)?->getName() ?? $prop->getName();
    }

    private function getClassDescription(ReflectionClass $class): ?string
    {
        if ($class->getDocComment() === false) {
            return null;
        }

        $tokens   = $this->docLexer->tokenize($class->getDocComment());
        $tokensIt = new TokenIterator($tokens);

        $node = $this->docParser->parse($tokensIt);

        $nodes = array_filter($node->children, fn(PhpDocChildNode $n) => $n instanceof PhpDocTextNode);
        $texts = array_map(fn(PhpDocTextNode $n) => $n->text, $nodes);
        $desc  = join(" ", $texts);

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

        $tokens   = $this->docLexer->tokenize($prop->getDocComment());
        $tokensIt = new TokenIterator($tokens);

        $node = $this->docParser->parse($tokensIt);

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
        // todo: optional annotation, objects, arrays

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
        $attrs = $obj->getAttributes($attributeClass);

        if (count($attrs) > 0) {
            return $attrs[0]->newInstance();
        }

        return null;
    }

    /**
     * @template T
     * @param class-string<T> $attribute
     * @return T[]
     */
    private function getAttributes(ReflectionProperty|ReflectionClass $obj, string $attribute): array
    {
        $attrs = [];

        $refAttrs = $obj->getAttributes($attribute);
        foreach ($refAttrs as $refAttr) {
            $attrs[] = $refAttr->newInstance();
        }

        return $attrs;
    }
}
