<?php

namespace AmirrezaNasiri\LaravelToman;

class VerifiedPayment
{
    private $referenceId;

    public function __construct($referenceId)
    {
        $this->referenceId = $referenceId;
    }

    public function getReferenceId()
    {
        return $this->referenceId;
    }
}
