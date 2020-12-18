<?php

namespace Evryn\LaravelToman\Support;

use Illuminate\Http\RedirectResponse;

trait RedirectsForPaying
{
    /**
     * Redirect user to final payment URL
     * @param array $options
     * @return mixed
     */
    public function pay(array $options = []): RedirectResponse {
        return redirect()->to($this->getPaymentUrl($options));
    }
}
