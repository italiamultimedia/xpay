<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Contract;

interface RequestInputServiceInterface
{
    public function getValidatedString(string $key): string;

    public function validateInputMac(): bool;
}
