<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Evryn\LaravelToman\Concerns\CallbackRequest as BaseCallbackRequest;

/**
 * Class Requester.
 */
class CallbackRequest extends BaseCallbackRequest
{
    protected function prepareForValidation()
    {
        if ($this->fakeVerification) {
            $this->merge([
                'id' => $this->fakeVerification->getTransactionId(),
                'order_id' => $this->fakeVerification->getOrderId(),
            ]);
        }
    }

    public function rules()
    {
        return [
            'id' => 'required|string',
            'order_id' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'id' => 'IDPay transaction id',
            'order_id' => 'IDPay order id',
        ];
    }

    public function getTransactionId()
    {
        return $this->input('id');
    }

    public function getOrderId()
    {
        return $this->input('order_id');
    }
}
