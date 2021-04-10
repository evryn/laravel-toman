<?php

namespace Evryn\LaravelToman\Concerns;

use Evryn\LaravelToman\FakeVerification;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;

abstract class CallbackRequest extends BaseFormRequest
{
    protected $stopOnFirstFailure = true;

    protected $fakeVerification;

    public function setFakeVerification(FakeVerification $fakeVerification)
    {
        $this->fakeVerification = $fakeVerification;
    }

    final public function validateResolved()
    {
    }

    public function validateCallback()
    {
        parent::validateResolved();
    }
}
