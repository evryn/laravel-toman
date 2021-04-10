<?php

namespace Evryn\LaravelToman;

class Money
{
    const TOMAN = 'toman';
    const RIAL = 'rial';
    /**
     * @var int
     */
    private $sourceAmount;
    /**
     * @var string
     */
    private $sourceCurrency;

    public function __construct(int $sourceAmount, string $sourceCurrency)
    {
        $this->validateCurrency($sourceCurrency);

        $this->sourceAmount = $sourceAmount;
        $this->sourceCurrency = $sourceCurrency;
    }

    public function getSourceCurrency()
    {
        return $this->sourceCurrency;
    }

    public function getSourceValue()
    {
        return $this->sourceAmount;
    }

    public function value(string $targetCurrency)
    {
        $this->validateCurrency($targetCurrency);

        if ($targetCurrency === $this->sourceCurrency) {
            return $this->sourceAmount;
        }

        return $this->fromToman(
            $this->toToman($this->sourceAmount, $this->sourceCurrency),
            $targetCurrency
        );
    }

    public static function Toman(int $amount)
    {
        return new self($amount, self::TOMAN);
    }

    public static function Rial(int $amount)
    {
        return new self($amount, self::RIAL);
    }

    public function is(self $money): bool
    {
        return $this->value(self::TOMAN) === $money->value(self::TOMAN);
    }

    protected function toToman(int $amount, string $currency): int
    {
        switch ($currency) {
            case self::TOMAN:
                return $amount;
            case self::RIAL:
                return $amount / 10;
        }
    }

    protected function fromToman(int $amount, string $currency): int
    {
        switch ($currency) {
            case self::TOMAN:
                return $amount;
            case self::RIAL:
                return $amount * 10;
        }
    }

    protected function validateCurrency(string $currency)
    {
        if (! in_array($currency, [self::TOMAN, self::RIAL])) {
            throw new \InvalidArgumentException("`{$currency}` is not supported as currency.");
        }
    }
}
