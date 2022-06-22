<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema;

use Exception;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\Schema;
use ReflectionClass;
use RuntimeException;

class Instantiator
{
    public function __construct()
    {
    }

    /**
     * @throws Exception
     */
    public function instantiate(Schema $schema, string $target, array $json): mixed
    {
        if (!($schema instanceof ObjectSchema)) {
            throw new RuntimeException("Not implemented.");
        }

        // todo asi brat $target ze Schema
        $rc = new ReflectionClass($target);

        $instance = $rc->newInstanceWithoutConstructor();
        foreach ($schema->getProperties() as $prop) {
            // todo name in json is not the same as name in $instance
            if (array_key_exists($prop->getName(), $json)) {
                $instance->{$prop->getName()} = $json[$prop->getName()];
            } else if ($prop->getSchema()->getDefault() !== null) {
                $instance->{$prop->getName()} = $prop->getSchema()->getDefault()->getValue();
            } else {
                throw new Exception("Property {$prop->getName()} not found in json.");
            }
        }

        return $instance;
    }
}
