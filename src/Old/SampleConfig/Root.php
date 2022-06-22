<?php declare(strict_types=1);

namespace MMartinHeralecky\Jsonschema\SampleConfig;

use DateTime;

class Root
{
    #[DateTimeCaster("Y-m-d")]
    #[Cast(DateTimeCaster::class, "Y-m-d")]
    #[Cast("castDateTime", "Y-m-d")]
    #[Cast([self::class, "castDateTime"], "Y-m-d")]
    public DateTime $date;

    public static function castDateTime(string $format): DateTime
    {
    }

    // todo default casters (i registrovatelny uzivatelem)

//    #[DefaultValue(new Idb())] // todo
//        public Idb $idb,

//        /**
//         * @var string[]
//         */
//        #[MinItems(2), UniqueItems]
//        public array $files,

//        #[Pattern("/^.+@.+\..+$/")]
//        public string $email,

    // todo validace nad itemama (Min, Max...)
}
