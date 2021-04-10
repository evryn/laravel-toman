<?php

namespace Evryn\LaravelToman\Concerns;

use Evryn\LaravelToman\Exceptions\GatewayException;
use Illuminate\Support\Arr;

trait InteractsWithResponse
{
    /**
     * @var GatewayException|null
     */
    protected $exception;
    /**
     * @var array
     */
    protected $messages;

    public function throw(): void
    {
        if ($this->failed()) {
            throw $this->exception;
        }
    }

    public function status()
    {
        return $this->failed() ? $this->exception->getCode() : null;
    }

    public function message(): ?string
    {
        return Arr::first(
            Arr::flatten(
                $this->messages()
            )
        );
    }

    public function messages(): array
    {
        if ($this->messages) {
            return $this->messages;
        }

        if ($this->failed()) {
            return [$this->exception->getMessage()];
        }

        return [];
    }
}
