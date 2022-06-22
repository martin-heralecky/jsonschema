<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Integration\Broker;

use MartinHeralecky\Jsonschema\Attribute\Enum;
use MartinHeralecky\Jsonschema\Attribute\Min;
use MartinHeralecky\Jsonschema\Attribute\Title;

/**
 * This is a specification of the configuration of the Broker application. It resides in a file named `config.yml` in
 * the root app directory.
 */
#[Title("Broker configuration")]
class Root
{
    /** @var string Environment in which the app runs. */
    #[Enum(["production", "development"])]
    public string $environment = "production"; // todo enum

    /**
     * @var string|int Lowest level of log entries that should be produced. For example, when set to `error`, only
     *                 `error`, `critical`, `alert` and `emergecy` log entries will be outputted.
     */
    #[Enum([
        100, "debug",
        200, "info",
        250, "notice",
        300, "warning",
        400, "error",
        500, "critical",
        550, "alert",
        600, "emergency",
    ])]
    public int|string $logLevel = "debug"; // todo enum

    public Api $api;

    public Idb $idb;

    /** @var int Maximum number of concurrent processes that load data to a UDM database. */
    #[Min(1)]
    public int $maxParallelism = 10;

    /**
     * @var int How many days should temporary directories with used CSV files be preserved. These are useful for
     *          debugging as they contain both the exact data that were retrieved from IDB and data that were loaded to
     *          UDM. Pruning takes place at the beginning of each run. `0` means that all CSV files are deleted at the
     *          beginning of each run.
     */
    #[Min(0)]
    public int $keepCsvDirsDays = 1;
}
