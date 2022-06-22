<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Integration\Broker;

use MartinHeralecky\Jsonschema\Attribute\Example;

/**
 * Config related to Cluster Manager Api.
 */
class Api
{
    /** @var string URL of the Cluster Manager Api. */
    #[Example("http://cm_api")]
    public string $url;
}
