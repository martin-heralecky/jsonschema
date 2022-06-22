<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Integration\Broker;

use MartinHeralecky\Jsonschema\Attribute\Example;

/**
 * Config related to IDB.
 */
class Idb
{
    /** @var string URL of the IDB. */
    #[Example("http://acme-live-main.lxd/idb")]
    public string $url;

    /** @var string Authorization token for HTTP requests to IDB Api. */
    public string $apiKey;

    /** @var int ID of the ALL datatype. */
    public int $allDataTypeId;

    /** @var int ID of the WAREHOUSE datatype. */
    public int $warehouseDataTypeId;

    /**
     * @var string Prefix of IDB tables. Only data from tables that have this prefix will be considered during data
     *             distribution.
     */
    #[Example("udm_")]
    public string $tablePrefix = "";

    /** @var string Host of the IDB database. */
    #[Example("acme-live-main.lxd")]
    public string $dbHost;

    /** @var int Port on which the IDB database is listening. */
    public int $dbPort;

    /** @var string Name of the IDB database. */
    public string $dbName;

    /** @var string Username to be used when connecting directly to the IDB database. */
    public string $dbUsername;

    /** @var string Password to be used when connecting directly to the IDB database. */
    public string $dbPassword;

    /**
     * @var string Name of the Pentaho job that transforms data of a given cluster and appears in
     *             `pdi_transformation_status.job_name`.
     */
    #[Example("acme-import-warehouse-in-cluster.kjb")]
    public string $jobName;
}
