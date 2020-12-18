<?php

namespace Evryn\LaravelToman\Support;

use Illuminate\Support\Arr;

trait HasData {
    /** @var array Payment gateway data holder */
    protected $data = [];

    public function data(string $key, $value = null)
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function getData(string $key = null)
    {
        return $key ? Arr::get($this->data, $key) : $this->data;
    }
}
