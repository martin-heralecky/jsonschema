<?php declare(strict_types=1);

use MartinHeralecky\Jsonschema\Introspector;
use MartinHeralecky\Jsonschema\JsonSchemaGenerator;
use MartinHeralecky\Jsonschema\Tests\Integration\Broker;

require_once __DIR__ . "/../vendor/autoload.php";

$introspector = new Introspector();
$schema       = $introspector->introspect(Broker\Root::class);
$generator    = new JsonSchemaGenerator();
echo json_encode($generator->generate($schema), JSON_PRETTY_PRINT) . "\n";
