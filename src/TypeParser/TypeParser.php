<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\TypeParser;

use MartinHeralecky\Jsonschema\TypeParser\Type\ArrayLikeType;
use MartinHeralecky\Jsonschema\TypeParser\Type\BuiltinType;
use MartinHeralecky\Jsonschema\TypeParser\Type\ClassType;
use MartinHeralecky\Jsonschema\TypeParser\Type\Type;
use MartinHeralecky\Jsonschema\TypeParser\Type\UnionType;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;
use Traversable;

/**
 * Among other things, encapsulates merging PHP type information with information in PHPDoc.
 *
 * @todo mozna pujde zrusit, protoze proste beru typ z phpdocu - pokud existuje phpdoc, tak ignoruju php typ - zadny
 *       slozity mergovani
 * @internal
 */
class TypeParser
{
    public function parseProperty(ReflectionType $type, ReflectionProperty $prop): Type
    {
        if ($type instanceof ReflectionNamedType) {
            if ($this->isArrayLike($type->getName())) {
                // todo: get with item type from phpdoc
                $theType = new ArrayLikeType(new BuiltinType("todo"));
            } elseif ($type->isBuiltin()) {
                $theType = new BuiltinType($type->getName());
            } elseif (class_exists($type->getName())) {
                $theType = new ClassType($type->getName());
            } else {
                throw new RuntimeException(sprintf(
                    "Unknown property type %s on %s::%s.",
                    $type,
                    $prop->getDeclaringClass()->getName(),
                    $prop->getName(),
                ));
            }

            if ($type->allowsNull() && $type->getName() !== "null") {
                return new UnionType([
                    $theType,
                    new BuiltinType("null"),
                ]);
            } else {
                return $theType;
            }
        } elseif ($type instanceof ReflectionUnionType) {
            $types = array_map(fn(ReflectionType $t) => $this->parseProperty($t, $prop), $type->getTypes());
            return new UnionType($types);
        } elseif ($type instanceof ReflectionIntersectionType) {
            throw new RuntimeException(sprintf(
                "Intersection types are not supported. Found on %s::%s.",
                $prop->getDeclaringClass()->getName(),
                $prop->getName(),
            ));
        } else {

            throw new RuntimeException(sprintf(
                "Unknown property type %s on %s::%s.",
                $type::class,
                $prop->getDeclaringClass()->getName(),
                $prop->getName(),
            ));
        }
    }

    private function isArrayLike(string $name): bool
    {
        if (in_array($name, ["array", "iterable"], true)) {
            return true;
        }

        if (class_exists($name) && is_subclass_of($name, Traversable::class)) {
            return true;
        }

        return false;
    }
}

//class IntersectionType{}

//class ObjectSlashMapLikeType{}
