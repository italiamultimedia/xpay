<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer;

final class Configuration
{
    public const string API_URL_PRODUCTION = 'https://ecommerce.nexi.it/';

    public const string API_URL_TEST = 'https://int-ecommerce.nexi.it/';

    /**
     * Used in simple payments, and recurring payments (initial).
     */
    public const string CURRENCY = 'EUR';

    /**
     * Used in recurring payments (subsequent).
     * https://ecommerce.nexi.it/specifiche-tecniche/pagamentoricorrente/pagamentisuccessivi.html
     * Unico valore ammesso: 978 (Euro)
     */
    public const int CURRENCY_CODE = 978;

    public const string ENVIRONMENT_PRODUCTION = 'production';

    public const string ENVIRONMENT_TEST = 'test';

    public const string RECURRING_PAYMENT_SUBSEQUENT_API_ENDPOINT = 'ecomm/api/recurring/pagamentoRicorrente';

    public const string SIMPLE_PAYMENT_API_ENDPOINT = 'ecomm/ecomm/DispatcherServlet';
}
