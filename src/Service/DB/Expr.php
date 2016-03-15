<?php

namespace Owl\Service\DB;

class Expr
{
    private $expr;

    public function __construct($expr)
    {
        $this->expr = (string) $expr;
    }

    public function __toString()
    {
        return $this->expr;
    }
}
