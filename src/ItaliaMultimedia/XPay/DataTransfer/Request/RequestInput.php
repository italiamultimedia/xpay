<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Request;

/**
 * Parameters received back form XPay.
 * https://ecommerce.nexi.it/specifiche-tecniche/codicebase.html
 */
final class RequestInput
{
    public const string COD_AUT = 'codAut';
    public const string COD_TRANS = 'codTrans';
    public const string DATA = 'data';
    public const string DIVISA = 'divisa';
    public const string ESITO = 'esito';
    public const string IMPORTO = 'importo';
    public const string MAC = 'mac';

    public const string ORARIO = 'orario';

    /** Recurring payments */

    // https://ecommerce.nexi.it/specifiche-tecniche/pagamentoricorrente/primopagamento.html
    public const string RECURRING_ID_INITIAL = 'num_contratto';

    // https://ecommerce.nexi.it/specifiche-tecniche/pagamentoricorrente/pagamentisuccessivi.html
    public const string RECURRING_ID_SUBSEQUENT = 'numeroContratto';
}
