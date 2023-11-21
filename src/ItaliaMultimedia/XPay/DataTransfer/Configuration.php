<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer;

final class Configuration
{
    public const API_URL_PRODUCTION = 'https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';

    public const API_URL_TEST = 'https://int-ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';

    public const CURRENCY = 'EUR';

    public const ENVIRONMENT_PRODUCTION = 'production';

    public const ENVIRONMENT_TEST = 'test';
}
