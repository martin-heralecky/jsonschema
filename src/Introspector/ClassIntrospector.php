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
use MartinHeralecky\Jsonschema\Schema\RefSchema;
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
    private array $classLikes = [];

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
        $this->classLikes = [];
        $schema = $this->introspectType(new AtomicType($class), []);

        $schema = $this->replaceRefs($schema);

        return $schema;
    }

    private function replaceRefs(Schema $schema): Schema
    {
        if ($schema instanceof ObjectSchema) {
            $props = $schema->getProperties();

            $atLeastOneRef = false;
            foreach ($props as $key => $prop) {
                if ($prop->getSchema() instanceof RefSchema) {

                    $atLeastOneRef = true;
                }
            }

            // todo: kdyz budu replacovat cely ObjectSchemas, nerozesere to strom? nemel bych pouzivat jen settery, aby se zachovaly reference?
            //       protoze asi chci aby $condition->union->allCondition->conditions->itemsSchema === $condition
            if (!$atLeastOneRef) {
                return $schema;
            }

            return new ObjectSchema(
                $schema->getTitle(),
                $schema->getDescription(),
                $schema->getPhpClass(),
                $props,
                $schema->getDefault(),
                $schema->getExamples(),
                $schema->getEnumValues(),
                $schema->getJsonToPhpCast(),
                $schema->getPhpToJsonCast(),
            );
        }
    }

    public function introspectType(Type $type, array $stack): Schema
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
                return new ArraySchema($this->introspectType($type->getGenericTypes()[0], $stack));
            }

            if (!array_key_exists($type->getName(), $this->classLikes)) {
                if (in_array($type->getName(), $stack, true)) {
                    // jiny zpusob: nejdriv naparsovat cely root objekt do RefSchemat (jen jeden layer - property toho root objektu)
                    // potom prochazet RefSchemata a resolvovat je (kdyz bude RefSchema(string), vrati StringSchema a ulozi do cache;
                    // kdyz bude RefSchema(Condition), vrati ObjectSchema(Prop(RefSchema(...)))). a takhle resolvovat dokud je neco k resolvovani.
                    // potom budu mit strom z RefSchematu a vsechny uz budu mit nekde jinde vyresolvovany do finalnich ne-RefSchematu (cache).
                    // takze jen nahradit RefSchema strom za ne-RefSchema strom.
                    $this->classLikes[$type->getName()] = new RefSchema($type->getName());
                } else {
                    $this->classLikes[$type->getName()] = $this->introspectClassLike($type->getName(), [...$stack, $type->getName()]);
                }
            }

            return $this->classLikes[$type->getName()];
        } else if ($type instanceof UnionType) {
            return new UnionSchema(array_map(fn(Type $type) => $this->introspectType($type, $stack), $type->getTypes()));
        }

        throw new RuntimeException("Unknown type " . $type::class . ".");
    }

    private function introspectClassLike(string $type, array $stack): Schema
    {
        try {
            $rc = new ReflectionClass($type);
        } catch (ReflectionException $e) {
            throw new IntrospectorException("Class does not exist: $type", previous: $e);
        }

        $title = $this->getAttribute($rc, Attribute\Title::class)?->getValue();
        $description = $this->getClassDescription($rc);

        $defaultAttr = $this->getAttribute($rc, Attribute\DefaultValue::class);
        if ($defaultAttr !== null) {
            $default = new Value($defaultAttr->getValue());
        } else {
            $default = null;
        }

        $jsonToPhpCast = $this->getAttribute($rc, JsonToPhpCast::class);
        $phpToJsonCast = $this->getAttribute($rc, PhpToJsonCast::class);

        if ($rc->isInterface()) {
            $subSchemas = [];
            foreach (get_declared_classes() as $class) {
                if (in_array($rc->getName(), class_implements($class), true)) {
                    $subSchemas[] = $this->introspectType(new AtomicType($class), $stack);
                }
            }

            return new UnionSchema(
                $subSchemas,
                $title,
                $description,
                $default,
                [], // todo
                [], // todo
                $jsonToPhpCast,
                $phpToJsonCast,
            );
        }

        $properties = [];
        foreach ($rc->getProperties() as $prop) {
            $properties[] = $this->introspectProperty($prop, $stack);
        }

        return new ObjectSchema(
            $title,
            $description,
            $type,
            $properties,
            $default,
            [], // todo
            [], // todo
            $jsonToPhpCast,
            $phpToJsonCast,
        );
    }

    private function introspectProperty(ReflectionProperty $prop, array $stack): ObjectSchemaProperty
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

        return new ObjectSchemaProperty(
            $this->getAttribute($prop, Attribute\Name::class)?->getValue() ?? $prop->getName(),
            $prop->getName(),
            $this->introspectType($type, $stack),
            $this->getPropertyDescription($prop),
            $default,
            $examples,
            $this->getAttribute($prop, Attribute\Enum::class)?->getValues() ?? [],
            $this->getAttribute($prop, JsonToPhpCast::class),
            $this->getAttribute($prop, PhpToJsonCast::class),
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
