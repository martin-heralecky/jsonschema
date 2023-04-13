<?php declare(strict_types=1);

namespace MartinHeralecky\Jsonschema\Attribute;

use Attribute;

// mozna nedava smysl aby byla na objektu ale jen na property. pak je totiz problem `public TypSDefaultem $neco = default` dvojiho defualtu.
// jde ale resit i v jsonschematu treba ze property ma default a v ni je oneOf s jedinnym itemem ktery taky ma defualt.
// je ale divny jak se to pak chova v ruznych situacich property ma/nema defualt, objekt ma/nema default.
#[Attribute(Attribute::TARGET_PROPERTY)]
class DefaultValue
{
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
