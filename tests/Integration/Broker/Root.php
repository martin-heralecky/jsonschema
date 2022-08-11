<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Tests\Integration\Broker;

use Attribute;
use MartinHeralecky\Jsonschema\Attribute\Enum;
use MartinHeralecky\Jsonschema\Attribute\Min;
use MartinHeralecky\Jsonschema\Attribute\Title;
use MartinHeralecky\Jsonschema\Attribute\Type;

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

    /**
     * @var LogLevel Lowest level of log entries that should be produced. For example, when set to `error`, only
     *               `error`, `critical`, `alert` and `emergecy` log entries will be outputted.
     */
    #[Type([TType::INT, TType::STRING])] // union
    #[Enum([100, 200, 250, 300, 400, 500, 550, 600], TType::INT)]
    #[Enum(["debug", "info", "notice", "warning", "error", "critical", "alert", "emergency"], TType::STRING)]
    #[Description("Order determines severity.", TType::INT)]
    public LogLevel $logLevel2 = LogLevel::debug;

    /** @var int[] */
    #[Type([new ArrayType(TType::INT), new ArrayType(TType::STRING)])]

    // kdyz to bude zanorene pole
    #[Schema(new ArraySchema("Title", "Description.", new StringSchema("Title of array item")))]
    // jen vymyslet aby se to dobre pozivalo a aby se dobre mergovaly informace z #[Schema()] a phpdocu a php typu

    // obecne jde o to, aby Schema pobralo uplne vsechno (nebo aspon bylo do budoucna pripravene uplne na vsechno)
    // a pak se jen muzou menit anotace / zpusob odkud se berou pro to Schema informace

    // typy v php a v jsonu jsou uplne oddelene - vubec nemusi souhlasit. je to code-first, takze se zacne v PHP a to tak
    // aby se to v PHP dobre pouzivalo. a pres #[Type()] se urci jak to ma vypadat v jsonu. implicitne to bude stejny typ
    // jak v PHP. a Casty jsou uplne zvlast.
    public array $someArray;

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

enum LogLevel
{
    case debug;
    case info;
    case notice;
    case warning;
    case error;
    case critical;
    case alert;
    case emergency;
}
