<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Enum;

enum Esito: string
{
    case ANNULLO = 'ANNULLO';

    case ERRORE = 'ERRORE';

    case KO = 'KO';

    case OK = 'OK';
}
