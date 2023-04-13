<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Introspector;

use MartinHeralecky\Jsonschema\Attribute;
use MartinHeralecky\Jsonschema\Cast\JsonToPhpCast;
use MartinHeralecky\Jsonschema\Cast\PhpToJsonCast;
use MartinHeralecky\Jsonschema\Exception\UnknownTypeException;
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

    /**
     * @param class-string $class
     * @throws IntrospectorException
     */
    public function introspect(string $class): Schema
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

    private function introspectProperty(ReflectionProperty $prop): Schema
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

    private function introspectAtomicPropertyTypeTmp(
        AtomicType $type,
        ?string $title,
        ?string $description,
        ?Value $default,
        array $examples,
        array $enumValues,
        ?JsonToPhpCast $jsonToPhpCast,
        ?PhpToJsonCast $phpToJsonCast,
        ?ReflectionProperty $prop,
    ): Schema {
        return $this->introspect($type->getName());
    }

    /**
     * @param ReflectionAttribute[] $attrs
     */
    private function createSchema(Type $type, ?string $desc, ?Value $default, array $attrs): Schema
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
     * @template T
     * @param ReflectionAttribute[] $attrs
     * @param class-string<T> $attrClass
     * @return T|null
     */
    private static function pop(array &$attrs, string $attrClass): ?object
    {
        foreach ($attrs as $key => $attr) {
            $inst = $attr->newInstance();
            if ($inst instanceof $attrClass) {
                unset($attrs[$key]);
                return $inst;
            }
        }

        return null;
    }

    /**
     * @template T
     * @param ReflectionAttribute[] $attrs
     * @param class-string<T> $attrClass
     * @return T[]
     */
    private static function popAll(array &$attrs, string $attrClass): array
    {
        $res = [];

        foreach ($attrs as $key => $attr) {
            if ($attr->getName() === $attrClass) {
                unset($attrs[$key]);
                $res[] = $attr->newInstance();
            }
        }

        return $res;
    }

    private function getPropertyName(ReflectionProperty $prop): string
    {
        return $this->getAttribute($prop, Attribute\Name::class)?->getValue() ?? $prop->getName();
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
