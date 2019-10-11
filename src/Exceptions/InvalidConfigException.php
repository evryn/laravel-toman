<?php

namespace AmirrezaNasiri\LaravelToman\Exceptions;

class InvalidConfigException extends Exception
{
    public function __construct($wrongKey)
    {
        parent::__construct("Wrong '$wrongKey' config is given.");
    }
}
