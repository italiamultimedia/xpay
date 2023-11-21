<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Request;

/**
 * Parameters received back form XPay.
 * https://ecommerce.nexi.it/specifiche-tecniche/codicebase.html
 */
final class RequestInput
{
    public const COD_AUT = 'codAut';
    public const COD_TRANS = 'codTrans';
    public const DATA = 'data';
    public const DIVISA = 'divisa';
    public const ESITO = 'esito';
    public const IMPORTO = 'importo';
    public const MAC = 'mac';
    public const ORARIO = 'orario';
}
