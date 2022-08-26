<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\TypeParser;

use MartinHeralecky\Jsonschema\Exception\UnknownTypeException;
use MartinHeralecky\Jsonschema\TypeParser\Type\AtomicType;
use MartinHeralecky\Jsonschema\TypeParser\Type\Type;
use MartinHeralecky\Jsonschema\TypeParser\Type\UnionType;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

/**
 * @internal
 */
class TypeParser
{
    /**
     * @throws UnknownTypeException
     * @todo Parse and use related PHPDoc.
     */
    public function parseProperty(ReflectionProperty $prop): Type
    {
        return $this->parseType($prop->getType(), $prop);
    }

    /**
     * @throws UnknownTypeException
     */
    private function parseType(ReflectionType $type, ReflectionProperty $prop): Type
    {
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin() || class_exists($type->getName())) {
                $theType = new AtomicType($type->getName());
            } else {
                throw new UnknownTypeException(sprintf(
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
            $types = array_map(fn(ReflectionType $t) => $this->parseType($t, $prop), $type->getTypes());
            return new UnionType($types);
        } elseif ($type instanceof ReflectionIntersectionType) {
            throw new UnknownTypeException(sprintf(
                "Intersection types are not supported. Found on %s::%s.",
                $prop->getDeclaringClass()->getName(),
                $prop->getName(),
            ));
        } else {
            throw new UnknownTypeException(sprintf(
                "Unknown property type %s on %s::%s.",
                $type::class,
                $prop->getDeclaringClass()->getName(),
                $prop->getName(),
            ));
        }
    }
}
