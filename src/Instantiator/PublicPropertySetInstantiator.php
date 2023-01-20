<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Instantiator;

use Exception;
use MartinHeralecky\Jsonschema\Schema\ObjectSchema;
use MartinHeralecky\Jsonschema\Schema\Schema;
use ReflectionClass;
use RuntimeException;

class PublicPropertySetInstantiator
{
    /**
     * @throws Exception
     */
    public function instantiate(Schema $schema, mixed $data): mixed
    {
        if (!($schema instanceof ObjectSchema)) {
            throw new RuntimeException("Not implemented.");
        }

        if ($schema->getPhpClass() === null) {
            throw new RuntimeException("Not instantiatable.");
        }

        $rc = new ReflectionClass($schema->getPhpClass());

        $instance = $rc->newInstanceWithoutConstructor();
        foreach ($schema->getProperties() as $prop) {
            if (array_key_exists($prop->getName(), $data)) {
                $instance->{$prop->getPhpName()} = $data[$prop->getName()];
            } else if ($prop->getSchema()->getDefault() !== null) {
                $instance->{$prop->getPhpName()} = $prop->getSchema()->getDefault()->getValue();
            } else {
                throw new Exception("Property {$prop->getName()} not found.");
            }
        }

        return $instance;
    }
}
