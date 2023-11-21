<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Request;

/**
 * Possible values of "esito" request parameter.
 * https://ecommerce.nexi.it/specifiche-tecniche/codicebase.html
 */
final class Esito
{
    public const ANNULLO = 'ANNULLO';
    public const ERRORE = 'ERRORE';
    public const KO = 'KO';

    public const OK = 'OK';

    public const VALUES = [
        self::ANNULLO,
        self::ERRORE,
        self::KO,
        self::OK,
    ];
}
