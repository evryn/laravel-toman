<?php

namespace AmirrezaNasiri\LaravelToman;

use Illuminate\Http\RedirectResponse;

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
