<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Introspector;

use MartinHeralecky\Jsonschema\Type\AtomicType;
use MartinHeralecky\Jsonschema\Type\Type;
use MartinHeralecky\Jsonschema\Type\UnionType;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types\ContextFactory;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser as PhpStanTypeParser;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;

/**
 * @internal
 */
class TypeParser
{
    /**
     * @throws IntrospectorException
     */
    public function parseProperty(ReflectionProperty $prop): Type
    {
        return $this->parsePhpDocType($prop) ?? $this->parsePropertyType($prop->getType(), $prop);
    }

    /**
     * @throws IntrospectorException
     */
    private function parsePhpDocType(ReflectionProperty $prop): ?Type
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

        $tag = current($tags);

        return $this->parseTypeNode($tag->type, $prop);
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

    private function parseTypeNode(TypeNode $type, ReflectionProperty $prop): Type
    {
        if ($type instanceof IdentifierTypeNode) {
            return new AtomicType($type->name);
        }

        if ($type instanceof ArrayTypeNode) {
            $itemType = $type->type;

            if ($itemType instanceof IdentifierTypeNode) {
                if (in_array($itemType->name, ["string", "int", "bool", "array", "mixed", "float"], true)) {
                    $n = $itemType->name;
                } else {
                    $cf = new ContextFactory();
                    $c = $cf->createFromReflector($prop);
                    $f = new FqsenResolver();
                    $n = (string)$f->resolve($itemType->name, $c);
                    $n = ltrim($n, "\\");
                }
                return new AtomicType("array", [new AtomicType($n)]); // todo $itemType->name je Condition bez namespacu. podivat se jak resi phpstan
            }
        }

        if ($type instanceof UnionTypeNode) {
            return new UnionType(array_map($this->parseTypeNode(...), $type->types, array_fill(0, count($type->types), $prop)));
        }

        throw new RuntimeException("Unknown property PhpDoc type $type.");
    }

    /**
     * @throws IntrospectorException()
     */
    private function parsePropertyType(ReflectionType $type, ReflectionProperty $prop): Type
    {
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                if ($type->getName() === "array") {
                    $theType = new AtomicType($type->getName(), [new AtomicType("mixed")]);
                } else {
                    $theType = new AtomicType($type->getName());
                }
            } else if (class_exists($type->getName()) || interface_exists($type->getName())) {
                $theType = new AtomicType($type->getName());
            } else {
                throw new IntrospectorException(sprintf(
                    "Unknown property type %s on %s::%s.",
                    $type->getName(),
                    $prop->getDeclaringClass()->getName(),
                    $prop->getName(),
                ));
            }

            if ($type->allowsNull() && $type->getName() !== "null") {
                return new UnionType([$theType, new AtomicType("null")]);
            } else {
                return $theType;
            }
        } elseif ($type instanceof ReflectionUnionType) {
            $types = array_map(fn(ReflectionType $t) => $this->parsePropertyType($t, $prop), $type->getTypes());
            return new UnionType($types);
        } elseif ($type instanceof ReflectionIntersectionType) {
            throw new IntrospectorException(sprintf(
                "Intersection types are not supported. Found on %s::%s.",
                $prop->getDeclaringClass()->getName(),
                $prop->getName(),
            ));
        } else {
            throw new IntrospectorException(sprintf(
                "Unknown property type %s on %s::%s.",
                $type::class,
                $prop->getDeclaringClass()->getName(),
                $prop->getName(),
            ));
        }
    }
}
