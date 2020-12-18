<?php

namespace Evryn\LaravelToman\Support;

use Illuminate\Support\Arr;

trait HasConfig {
    /** @var array Driver config */
    protected $config;

    protected function setConfig(array $config = [])
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(string $key = null)
    {
        return $key ? Arr::get($this->config, $key) : $this->config;
    }
}
