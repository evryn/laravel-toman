<?php

namespace AmirrezaNasiri\LaravelToman\Results;

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
